<?php
class Gongo_Fn
{
	static function quote($obj)
	{
		return Gongo_Locator::get('Gongo_Fn_Quotation', $obj);
	}

	static function callee()
	{
		list(, $frame) = debug_backtrace() + array(1 => false);
		if (!$frame) throw new BadFunctionCallException('You must call in function');
		$callback = isset($frame['object']) ? array($frame['object'], $frame['function']) :
			(isset($frame['class']) ? array($frame['class'], $frame['function']) :
			$frame['function']);
		$args = func_get_args();
		return $args ? Gongo_Fn_Partial::apply($callback, $args) : $callback;
	}

	static function method($name)
	{
		list(, $frame) = debug_backtrace() + array(1 => false);
		if (!isset($frame['class'])) throw new BadFunctionCallException('You must call in class method');
		$callback = array(isset($frame['object']) ? $frame['object'] : $frame['class'], $name);
		$args = func_get_args();
		array_shift($args);
		return $args ? Gongo_Fn_Partial::apply($callback, $args) : $callback;
	}

	static function papply($callback)
	{
		$args = func_get_args();
		array_shift($args);
		return Gongo_Fn_Partial::apply($callback, $args);
	}

	static function call($callback)
	{
		$args = func_get_args();
		array_shift($args);
		return call_user_func_array($callback, $args);
	}

	static function apply($callback, $args)
	{
		return call_user_func_array($callback, $args);
	}

	static function once($callback)
	{
		return Gongo_Locator::get('Gongo_Fn_Once', $callback);
	}

	static function create($callback, $args = array())
	{
		return new Gongo_Fn_Partial($callback, $args);
	}
	
	static function before($callback, $before)
	{
		return Gongo_Fn::create($callback)->before($before)->fetch();
	}

	static function after($callback, $after)
	{
		return Gongo_Fn::create($callback)->after($after)->fetch();
	}

	static function around($callback, $around)
	{
		return Gongo_Fn::create($callback)->around($around)->fetch();
	}
	
	static function reflection($callback)
	{
		if (is_string($callback)) {
			if (strpos($callback, '::') === false) {
				// function
				return new ReflectionFunction($callback);
			} else {
				// static method
				return new ReflectionMethod($callback);
			}
		} else if (is_array($callback)) {
			$func = $callback[0];
			if (is_string($func)) {
				// function or static method
				return self::reflection($func);
			} else if (is_object($func)) {
				if ($func instanceof Gongo_Fn_Partial) {
					// partial application object
					return $func;
				} else {
					// instance method
					return new ReflectionMethod(get_class($func), $callback[1]);
				}
			}
		}
		return null;
	}
	
	static function params($callback)
	{
		$ref = self::reflection($callback);
		return $ref ? $ref->getParameters() : null ;
	}

	static function arity($callback)
	{
		$ref = self::reflection($callback);
		return $ref ? $ref->getNumberOfParameters() : null ;
	}
	
	static function curry($callback)
	{
		return Gongo_Fn_Curry::make($callback);
	}

	static function property($obj)
	{
		return Gongo_Locator::get('Gongo_Fn_Property', $obj);
	}
}

