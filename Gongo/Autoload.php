<?php
class Gongo_Autoload
{
	static $gongoPath = null;
	static $limit = false;

	public static function autoload($sClass)
	{
		if (self::$limit && strpos($sClass, 'Gongo_') !== 0) return;
		if (is_null(self::$gongoPath)) {
			self::$gongoPath = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
		}
		$sPath = self::$gongoPath . strtr($sClass, array('_' => DIRECTORY_SEPARATOR)).'.php';
		is_file($sPath) and include($sPath);
	}

	public static function register($limit = true)
	{
		self::$limit = $limit;
		spl_autoload_register('Gongo_Autoload::autoload');
		if (function_exists('__autoload')) {
			spl_autoload_unregister('__autoload');
			spl_autoload_register('__autoload');
		}
	}

	public static function unregister()
	{
		spl_autoload_unregister('Gongo_Autoload::autoload');
	}
}

Gongo_Autoload::register();

class Gongo
{
	static function get()
	{
		$args = func_get_args();
		$className = array_shift($args);
		return Gongo_Locator::make('Gongo_' . $className, $args);
	}
	
	function __get($name)
	{
		return self::get($name);
	}

	function __call($name, $args)
	{
		return Gongo_Locator::make('Gongo_' . $name, $args);
	}
}
