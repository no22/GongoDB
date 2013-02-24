<?php
class Gongo_Fn_Once
{
	protected $callback = null;
	protected $value = null;
	protected $invoked = false;

	public function __construct($callback)
	{
		$this->callback = $callback;
		$this->value = null;
		$this->invoked = false;
	}

	public function __invoke()
	{
		return $this->invoke();
	}

	public function invoke()
	{
		if (!$this->invoked) {
			$this->value = call_user_func($this->callback);
			$this->invoked = true;
		}
		return $this->value;
	}
}
