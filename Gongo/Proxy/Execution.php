<?php
class Gongo_Proxy_Execution extends Gongo_Proxy 
{
	public $_____callbacks = array();
	public $_____getterCallbacks = array();
	public $_____setterCallbacks = array();
		
	public function __call($method, $args)
	{
		if (strpos($method, '_before') === 0 && strlen($method) > 7) {
			$method = strtolower(substr($method, 7, 1)) . substr($method, 8);
			list($before) = $args;
			$callback = isset($this->_____callbacks[$method]) ? $this->_____callbacks[$method] : array($this->_____, $method) ;
			return $this->_____callbacks[$method] = Gongo_Fn::before($callback, $before);
		} else if (strpos($method, '_after') === 0 && strlen($method) > 6) {
			$method = strtolower(substr($method, 6, 1)) . substr($method, 7);
			list($after) = $args;
			$callback = isset($this->_____callbacks[$method]) ? $this->_____callbacks[$method] : array($this->_____, $method) ;
			return $this->_____callbacks[$method] = Gongo_Fn::after($callback, $after);
		} else if (strpos($method, '_around') === 0 && strlen($method) > 7) {
			$method = strtolower(substr($method, 7, 1)) . substr($method, 8);
			list($around) = $args;
			$callback = isset($this->_____callbacks[$method]) ? $this->_____callbacks[$method] : array($this->_____, $method) ;
			return $this->_____callbacks[$method] = Gongo_Fn::around($callback, $around);
		} else if (strpos($method, '_getter') === 0 && strlen($method) > 7) {
			$property = strtolower(substr($method, 7, 1)) . substr($method, 8);
			list($around) = $args;
			if (isset($this->_____getterCallbacks[$property])) {
				$callback = $this->_____getterCallbacks[$property];
			} else {
				$callback = Gongo_Fn::property($this->_____)->getter($property);
			}
			return $this->_____getterCallbacks[$property] = Gongo_Fn::around($callback, $around);
		} else if (strpos($method, '_setter') === 0 && strlen($method) > 7) {
			$property = strtolower(substr($method, 7, 1)) . substr($method, 8);
			list($around) = $args;
			if (isset($this->_____setterCallbacks[$property])) {
				$callback = $this->_____setterCallbacks[$property];
			} else {
				$callback = Gongo_Fn::property($this->_____)->setter($property);
			}
			return $this->_____setterCallbacks[$property] = Gongo_Fn::around($callback, $around);
		}
		if (isset($this->_____callbacks[$method])) {
			return call_user_func_array($this->_____callbacks[$method], $args);
		}
		return parent::__call($method, $args);
	}

	public function __get($property) 
	{
		if (isset($this->_____getterCallbacks[$property])) {
			return call_user_func($this->_____getterCallbacks[$property]);
		}
		return parent::__get($property);
	}

	public function __set($property, $value) 
	{
		if (isset($this->_____setterCallbacks[$property])) {
			return call_user_func($this->_____setterCallbacks[$property], $value);
		}
		return parent::__set($property);
	}
}
