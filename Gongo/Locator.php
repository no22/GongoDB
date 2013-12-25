<?php
class Gongo_Locator
{
	static public $defaultConfig = null;
	static $serviceLocator = null;
	static $defaultBuilder = 'Gongo_Builder';
	static $environmentVariable = 'GONGO_BUILDER';
	protected $serviceBuilder = null;
	protected $config = null;
	protected $refParams = array();
	protected $refClasses = array();

	static function setConfig($cfg = null)
	{
		self::$defaultConfig = is_null($cfg) ? Gongo_Config::get() : $cfg ;
	}

	static public function getInstance()
	{
		if (is_null(self::$serviceLocator)) {
			self::$serviceLocator = new self;
		}
		return self::$serviceLocator;
	}

	static public function get()
	{
		$serviceLocator = self::getInstance();
		$args = func_get_args();
		$className = array_shift($args);
		return self::build($serviceLocator, $className, $args);
	}

	static public function make($className, $args)
	{
		$serviceLocator = self::getInstance();
		return self::build($serviceLocator, $className, $args);
	}

	static public function _make($className, $args)
	{
		return Gongo_Fn::papply('Gongo_Locator::make', $className, $args);
	}

	static public function _get()
	{
		$args = func_get_args();
		$className = array_shift($args);
		return self::_make($className, $args);
	}

	static public function makeLazy($className, $args, $after = null, $singleton = false)
	{
		$callback = Gongo_Fn::papply('Gongo_Locator::make', $className, $args);
		if (!is_null($after)) $callback = Gongo_Fn::after($callback, $after);
		if ($singleton) $callback = Gongo_Fn::once($callback);
		return self::get('Gongo_Proxy_Lazy', $callback);
	}

	static public function getLazy()
	{
		$args = func_get_args();
		$className = array_shift($args);
		return self::makeLazy($className, $args);
	}

	static public function build($serviceLocator, $className, $args)
	{
		$serviceBuilder = $serviceLocator->builder();
		$method = 'build_' . $className;
		if (!is_null($serviceBuilder) && method_exists($serviceBuilder, $method)) {
			$obj = call_user_func_array(array($serviceBuilder, $method), $args);
			if (!is_null($obj)) return $obj;
		}
		$config = $serviceLocator->config();
		if (!is_null($config)) {
			$className = $config->Locator->{$className} ? $config->Locator->{$className} : $className ;
		}
		return $serviceLocator->newObj($className, $args);
	}

	static function load($path, $className = null, $autoload = false)
	{
		if (!is_null($className) && class_exists($className, $autoload)) return;
		include($path);
	}

	public function builder($builder = null)
	{
		if (is_null($builder)) return $this->serviceBuilder;
		$this->serviceBuilder = $builder;
		return $this;
	}

	public function injectBuilder($builderClass, $args = array())
	{
		$this->builder($this->newObj($builderClass, $args));
	}

	public function config($config = null)
	{
		if (is_null($config)) return $this->config;
		$this->config = $config;
		return $this;
	}

	public function __construct($builder = null)
	{
		if (!is_null(self::$defaultConfig)) {
			$this->config(self::$defaultConfig);
		}
		if (is_null($builder)) {
			$environmentVariable = self::$environmentVariable;
			$builderClass = isset($_SERVER[$environmentVariable]) ? $_SERVER[$environmentVariable] : false ;
			if (!$builderClass) $builderClass = getenv($environmentVariable);
			if (!$builderClass) $builderClass = self::$defaultBuilder;
			$config = $this->config();
			if (!is_null($config)) {
				$builderClass = $config->Locator->Gongo_Builder ? $config->Locator->Gongo_Builder : $builderClass ;
			}
			$builder = $this->newObj($builderClass);
		}
		$this->builder($builder);
	}

	public function getObj()
	{
		$serviceBuilder = $this->builder();
		$args = func_get_args();
		$className = array_shift($args);
		return self::build($this, $className, $args);
	}

	public function makeObj($className, $args)
	{
		return self::build($this, $className, $args);
	}

	protected function newObj($sClass, $args = array())
	{
		if (!$sClass) return null;
		if (count($args) === 0) return new $sClass;
		if (isset($this->refParams[$sClass])) {
			$params = $this->refParams[$sClass];
		} else {
			$refMethod = new ReflectionMethod($sClass,  '__construct');
			$params = $this->refParams[$sClass] = $refMethod->getParameters();
		}
		$re_args = array();
		foreach($params as $key => $param) {
			if (isset($args[$key])) {
				if ($param->isPassedByReference()) {
					$re_args[$key] = &$args[$key];
				} else {
					$re_args[$key] = $args[$key];
				}
			}
		}
		$refClass = isset($this->refClasses[$sClass]) ? $this->refClasses[$sClass] : $this->refClasses[$sClass] = new ReflectionClass($sClass) ;
		return $refClass->newInstanceArgs((array) $re_args);
	}
}
