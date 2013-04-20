<?php
class Gongo_Bean_Base 
{
	public $__ = null;
	
	public function __get($key)
	{
		if ($key === '_') {
			if (is_null($this->__)) return null;
			$aComponents = $this->___initializeComponents();
			$this->_ = Gongo_Locator::get('Gongo_Container', $aComponents);
			return $this->_;
		}
		return null;
	}

	public function ___componentClasses($sClass = null)
	{
		$sClass = is_null($sClass) ? get_class($this) : $sClass ;
		$aVars = get_class_vars($sClass);
		$aComponents = isset($aVars['__']) ? $aVars['__'] : array() ;
		$sParent = get_parent_class($sClass);
		if (!$sParent) return $aComponents;
		$aParentComponents = $this->___componentClasses($sParent);
		return array_merge($aParentComponents, $aComponents);
	}

	public function ___initializeComponents($aInjectComponents = null)
	{
		$aComponents = $this->___componentClasses();
		if (!is_null($aInjectComponents)) {
			$aComponents = array_merge($aComponents, $aInjectComponents);
		}
		return $aComponents;
	}
}
