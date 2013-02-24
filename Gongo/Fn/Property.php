<?php
class Gongo_Fn_Property
{
	protected $___;
	
	function __construct($obj)
	{
		$this->___ = $obj;
	}

	function getProperty($property)
	{
		return $this->___->{$property};
	}

	function setProperty($property, $value)
	{
		$this->___->{$property} = $value;
	}

	function getter($property)
	{
		return Gongo_Fn::quote($this)->getProperty($property);
	}

	function setter($property)
	{
		return Gongo_Fn::quote($this)->setProperty($property);
	}
	
	function __get($property)
	{
		if (strpos($property, 'get') === 0 && strlen($property) > 3) {
			$property = strtolower(substr($property, 3, 1)) . substr($method, 4);
			return $this->getter($property);
		} else if (strpos($property, 'set') === 0 && strlen($property) > 3) {
			$property = strtolower(substr($property, 3, 1)) . substr($method, 4);
			return $this->setter($property);
		}
		throw new OutOfBoundsException("Undefined property: {$property} ");
	}	
}
