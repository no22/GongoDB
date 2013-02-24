<?php
class Gongo_Fn_Partial
{
	protected $callback;
	protected $arguments;
	protected $before;
	protected $after;
	protected $around;
	
	public function __construct($callback, Array $args = array())
	{
		if (!is_callable($callback)) throw new InvalidArgumentException('$callback must be callable');
		$this->callback = $callback;
		$this->arguments = $args;
	}
	
	static function apply($callback, $args = null)
	{
		return $args ? array(new self($callback, $args), 'invoke') : $callback;
	}
	
	function before($before = null)
	{
		$this->before = $before;
		return $this;
	}

	function after($after = null)
	{
		$this->after = $after;
		return $this;
	}

	function around($around = null)
	{
		$this->around = $around;
		return $this;
	}
	
	function fetch($args = array())
	{
		if (!empty($args)) {
			$this->arguments = array_merge($this->arguments, $args);
		}
		return array($this, 'invoke');
	}
	
	function invoke()
	{
		$args = func_get_args();
		if ($this->before) {
			$returnArgs = call_user_func($this->before, $args);
			if (!is_array($returnArgs) && !is_null($returnArgs)) {
				return $returnArgs;
			}
			$args = !is_null($returnArgs) ? $returnArgs : $args ;
		}
		$args = array_merge($this->arguments, $args);
		if ($this->around) {
			$returnValue = call_user_func_array($this->around, array($this->callback, $args));
		} else {
			$returnValue = call_user_func_array($this->callback, $args);
		}
		if ($this->after) {
			return call_user_func($this->after, $returnValue);
		}
		return $returnValue;
	}
	/**
	 * __invoke
	 * for PHP5.3
	 */
	public function __invoke()
	{
		return call_user_func_array(array($this, 'invoke'), func_get_args());
	}

	public function getParameters()
	{
		$params = Gongo_Fn::params($this->callback);
		return !is_null($params) ? array_slice($params, count($this->arguments)) : null ;
	}

	public function getNumberOfParameters()
	{
		$arity = Gongo_Fn::arity($this->callback);
		return !is_null($arity) ? $arity - count($this->arguments) : null ;
	}
}