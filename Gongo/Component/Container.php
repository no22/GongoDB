<?php
class Gongo_Component_ContainerException extends Exception {}

class Gongo_Component_Container
{
	protected $components = array();
	protected $callbacks = array();
	
	function __get($name)
	{
		if (array_key_exists($name, $this->components)) {
			return $this->components[$name];
		}
		if (array_key_exists($name, $this->callbacks)) {
			$this->components[$name] = call_user_func($this->callbacks[$name]);
			return $this->components[$name];
		}
		$method = '_' . $name;
		if (method_exists($this, $method)) {
			$this->components[$name] = $this->{$method}();
			return $this->components[$name];
		}
		throw new Gongo_Component_ContainerException('method not found: '. get_class($this) . '::' . $method);
	}

	function __call($name, $args)
	{
		if (array_key_exists($name, $this->components)) {
			return $this->components[$name];
		}
		$method = '_' . $name;
		if (!method_exists($this, $method)) {
			throw new Gongo_Component_ContainerException('method not found: '. get_class($this) . '::' . $method);
		}
		$this->components[$name] = call_user_func_array(array($this, $method), $args);
		return $this->components[$name];
	}
	
	function _($name, $callback)
	{
		$this->callbacks[$name] = $callback;
		return $this;
	}
}
