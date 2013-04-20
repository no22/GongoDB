<?php

if (!defined('GONGO_ROOT')) {
	define('GONGO_ROOT', dirname(dirname(__FILE__)));
}

class Gongo_Autoload
{
	static $gongoPath = null;
	static $limit = true;

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

if (!defined('PHP_VERSION_ID')) {
	$version = explode('.', PHP_VERSION);
	define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

if (!function_exists('quote')) {
    function quote($obj) { return Gongo_Fn::quote($obj); }
}

if (!function_exists('bind')) {
    function bind($callback) { 
		$args = func_get_args();
		array_shift($args);
		return Gongo_Fn_Partial::apply($callback, $args);
	}
}

if (!function_exists('call')) {
	function call($callback)
	{
		$args = func_get_args();
		array_shift($args);
		return call_user_func_array($callback, $args);
	}
}

if (!function_exists('apply')) {
	function apply($callback, $args)
	{
		return call_user_func_array($callback, $args);
	}
}

if (!function_exists('once')) {
	function once($callback)
	{
		return Gongo_Fn::once($callback);
	}
}

if (!function_exists('before')) {
	function before($callback, $before)
	{
		return Gongo_Fn::before($callback, $before);
	}
}

if (!function_exists('after')) {
	function after($callback, $after)
	{
		return Gongo_Fn::after($callback, $after);
	}
}

if (!function_exists('around')) {
	function around($callback, $around)
	{
		return Gongo_Fn::around($callback, $around);
	}
}
