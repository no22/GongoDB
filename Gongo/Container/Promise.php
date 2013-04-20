<?php
class Gongo_Container_Promise
{
	public $__obj;
	public $__name;
	
	public function __construct($obj, $name) 
	{
		$this->__obj = $obj; 
		$this->__name = $name; 
	}

	public function __get($sName) 
	{
		if (strpos($sName, '_', 0) === 0) {
			return new self($this, substr($sName, 1));
		}
		return $this->__obj->{$this->__name}->{$sName};
	}
	
	public function __force()
	{
		$aArgs = func_get_args();
		$sName = array_shift($aArgs);
		$aBind = array_shift($aArgs);
		$aArgs = array_merge($aBind, $aArgs);
		return call_user_func_array(array($this->__obj->{$this->__name}, $sName), $aArgs);
	}
	
	public function __call($sName, $aArg)
	{
		if (strpos($sName, '_', 0) === 0) {
			return Gongo_Fn_Partial::apply(array($this, '__force'), array(substr($sName, 1), $aArg));
		}
		return call_user_func_array(array($this->__obj->{$this->__name}, $sName), $aArg);
	}
}
