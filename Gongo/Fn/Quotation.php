<?php
class Gongo_Fn_Quotation
{
	protected $___;
	
	function __construct($obj)
	{
		$this->___ = $obj;
	}
	function __get($name)
	{
		return array($this->___, $name);
	}
	function __call($name, $args)
	{
		return Gongo_Fn_Partial::apply($this->{$name}, $args);
	}
}
