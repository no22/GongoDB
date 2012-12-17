<?php
class Gongo_Locator
{
	static public $defaultConfig = null;
	static $serviceLocator = null;
	protected $serviceBuilder = null;
	protected $config = null;
	
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
			$builderClass = 'Gongo_Builder';
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
		$refMethod = new ReflectionMethod($sClass,  '__construct');
		$params = $refMethod->getParameters();
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
		$refClass = new ReflectionClass($sClass);
		return $refClass->newInstanceArgs((array) $re_args);
	}
}
