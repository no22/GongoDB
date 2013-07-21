<?php
class Gongo_Proxy_Lazy extends Gongo_Proxy
{
	public $______ = null;

	public function __construct($callback)
	{
		$this->______ = $callback;
	}

	public function _____()
	{
		if (is_null($this->_____)) $this->_____ = call_user_func($this->______);
		return $this->_____;
	}

	public function __destruct()
	{
		$this->_____ = null;
		parent::__destruct();
	}

	public function __call($method, $args)
	{
		$this->_____();
		return parent::__call($method, $args);
	}

	public function __get($property)
	{
		$this->_____();
		return parent::__get($property);
	}
	
	public function __set($property, $value)
	{
		$this->_____();
		parent::__set($property, $value);
	}

	public function __isset($property)
	{
		$this->_____();
		return parent::__isset($method, $args);
	}

	public function __unset($property)
	{
		$this->_____();
		parent::__unset($property);
	}

	public function __toString()
	{
		$this->_____();
		return parent::__toString();
	}
}
