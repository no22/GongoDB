<?php
class Gongo_Container
{
	protected $factory = null;
	protected $components = array();
	public $uses = array();

	public function __construct($aComponents = array())
	{
		$this->initFactory($aComponents);
	}

	public function initFactory($aComponents = array())
	{
		$this->factory = Gongo_Locator::getInstance();
		$this->initializeComponents($aComponents);
	}

	public function __call($sName, $aArg)
	{
		if ($sName[0] === '_') {
			return Gongo_Fn_Partial::apply(array($this, substr($sName, 1)), $aArg);
		}
	}

	public function __get($sName)
	{
		if ($sName[0] === '_') {
			if ($sName[1] !== '_') {
				return $this->factory->getObj('Gongo_Container_Promise', $this, substr($sName, 1));
			}
			$sName = substr($sName, 2);
			return isset($this->components[$sName]);
		} else if (isset($this->components[$sName])) {
			return $this->{$sName} = call_user_func($this->components[$sName]);
		}
		return null;
	}

	protected function mergeComponents($aParentComponents, $aComponents)
	{
		foreach ($aComponents as $key => $value) {
			if ($key[0] === '+' && is_array($value)) {
				$normKey = '-' . substr($key, 1);
				if (isset($aParentComponents[$key])) {
					$aParentComponents[$normKey] = $aParentComponents[$key];
					unset($aParentComponents[$key]);
				}
				if (isset($aParentComponents[$normKey])) {
					$aComponents[$normKey] = array_merge($aParentComponents[$normKey], $value);
				} else {
					$aComponents[$normKey] = $value;
				}
				unset($aComponents[$key]);
			}
		}
		return array_merge($aParentComponents, $aComponents);
	}

	public function componentClasses($sClass = null)
	{
		$sClass = is_null($sClass) ? get_class($this) : $sClass ;
		$aVars = get_class_vars($sClass);
		$aComponents = isset($aVars['uses']) ? $aVars['uses'] : array() ;
		$sParent = get_parent_class($sClass);
		if (!$sParent) return $aComponents;
		$aParentComponents = $this->componentClasses($sParent);
		return $this->mergeComponents($aParentComponents, $aComponents);
	}

	public function initializeComponents($aInjectComponents = null)
	{
		$aComponents = $this->componentClasses();
		if (!is_null($aInjectComponents)) {
			$aComponents = $this->mergeComponents($aComponents, $aInjectComponents);
		}
		$aOptions = array();
		foreach ($aComponents as $sKey => $sClass) {
			if ($sKey[0] === '-') {
				$aOptions[substr($sKey,1)] = $sClass;
			} else if (is_array($sClass)) {
				$args = $sClass;
				$sClass = array_shift($args);
				$sName = is_string($sKey) ? $sKey : $sClass ;
				$this->components[$sName] = Gongo_Fn::quote($this->factory)->makeObj($sClass, $args);
			} else if (!is_null($sClass)) {
				$sName = is_string($sKey) ? $sKey : $sClass ;
				$this->components[$sName] = Gongo_Fn::quote($this->factory)->getObj($sClass);
			}
		}
		$this->components['options'] = Gongo_Fn::quote($this->factory)->getObj('Gongo_Bean_ArrayWrapper', $aOptions);
	}

	public function attach($mName, $mClass = null)
	{
		if (is_array($mName)) {
			$sClass = array_shift($mName);
			if(property_exists($this, $sClass)) unset($this->{$sClass});
			$this->components[$sClass] = Gongo_Fn::quote($this->factory)->makeObj($sClass, $mName);
			return $this;
		}
		if (is_array($mClass)) {
			$sClass = array_shift($mClass);
		} else {
			$sClass = is_null($mClass) ? $mName : $mClass ;
		}
		if(property_exists($this, $mName)) unset($this->{$mName});
		if (is_array($mClass)) {
			$this->components[$mName] = Gongo_Fn::quote($this->factory)->makeObj($sClass,$mClass);
		} else {
			$this->components[$mName] = Gongo_Fn::quote($this->factory)->getObj($sClass);
		}
		return $this;
	}

	public function afterInit($sName, $callback)
	{
		if (isset($this->components[$sName])) {
			$this->components[$sName] = Gongo_Fn::after($this->components[$sName], $callback);
		}
	}

	public function register($sName, $callback)
	{
		$this->components[$sName] = $callback;
	}

	public function defaultValue($options, $sName, $mValue)
	{
		if (!isset($options[$sName])) {
			$options[$sName] = $mValue;
		}
		return $options;
	}
}
