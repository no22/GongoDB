<?php
class Gongo_Proxy 
{
	public $_____ = null;

	public function __construct($obj)
	{
		$this->_____ = $obj;
	}

	public function __destruct()
	{
		$this->_____ = null;
	}

	public function __call($method, $args)
	{
		return call_user_func_array(array($this->_____, $method), $args);
	}

	public function __get($property)
	{
		return $this->_____->{$property};
	}
	
	public function __set($property, $value)
	{
		$this->_____->{$property} = $value;
	}

	public function __isset($property)
	{
		return isset($this->_____->{$property});
	}

	public function __unset($property)
	{
		unset($this->_____->{$property});
	}

	public function __toString()
	{
		return $this->_____->__toString();
	}
}
