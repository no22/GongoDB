<?php
class Gongo_Fn_Curry
{
	protected $callback;
	protected $arity;
	protected $arguments = array();
	
	static function make($callback)
	{
		$obj = new self($callback);
		return array($obj, 'invoke');
	}
	
	function __construct($callback)
	{
		$this->callback = $callback;
		$this->arity = Gongo_Fn::arity($callback);
	}
	
	function invoke($arg = null)
	{
		if ($this->arity <= 0) return Gongo_Fn::call($this->callback);
		$this->arguments[] = $arg;
		if ($this->arity <= count($this->arguments)) {
			return Gongo_Fn::apply($this->callback, $this->arguments);
		}
		return array($this, 'invoke');
	}
	
	function __invoke($arg = null)
	{
		return $this->invoke($arg);
	}
}
