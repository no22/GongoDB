<?php
class Gongo_DI_Container
{
	public $services = array();

	public function registerComponent($name, $component, $dependency, $lazy = false)
	{
		$this->services[$name] = array($component, $dependency, $lazy);
		return $this;
	}
	
	public function register($name, $component)
	{
		$dependency = func_get_args();
		$name = array_shift($dependency);
		$component = array_shift($dependency);
		return $this->registerComponent($name, $component, $dependency, false);
	}

	public function registerLazy($name, $component)
	{
		$dependency = func_get_args();
		$name = array_shift($dependency);
		$component = array_shift($dependency);
		return $this->registerComponent($name, $component, $dependency, true);
	}

	public function instance($name, $eager = null)
	{
		list($component, $dependency, $lazy) = $this->services[$name];
		$args  = array();
		foreach ($dependency as $service) {
			$args[] = $this->instance($service, $eager);
		}
		if (!is_null($eager)) {
			return $eager ? Gongo_Locator::make($component, $args) : Gongo_Locator::makeLazy($component, $args) ;
		}
		return $lazy ? Gongo_Locator::makeLazy($component, $args) : Gongo_Locator::make($component, $args) ;
	}
}
