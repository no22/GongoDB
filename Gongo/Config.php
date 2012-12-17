<?php
class Gongo_Config extends Gongo_Bean
{
	static $config = null;
	
	static function get($cfg = null)
	{
		if (is_null(self::$config)) {
			$cfg = is_null($cfg) ? dirname(__FILE__) . '/config.ini' : $cfg ;
			self::$config = Gongo_Locator::get('Gongo_Config', $cfg);
		}
		return self::$config;
	}
	
	function __construct($cfg = array())
	{
		$cfg = is_string($cfg) ? parse_ini_file($cfg, true) : $cfg ;
		parent::__construct($cfg);
	}
}
