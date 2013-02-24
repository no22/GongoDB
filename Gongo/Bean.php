<?php
class Gongo_Bean implements IteratorAggregate
{
	protected $_data;
	
	static function import($bean, $data, $attr = null)
	{
		$ary = array();
		foreach ($data as $k => $v) {
			if (is_null($attr) || in_array($k, $attr)) {
				$ary[$k] = is_array($v) ? Gongo_Locator::get('Gongo_Bean', $v) : $v ;
			}
		}
		return $bean->_($ary);
	}

	static function export($bean)
	{
		$ary = array();
		foreach ($bean->_() as $k => $v) {
			$ary[$k] = $v instanceof Gongo_Bean ? self::export($v) : $v ;
		}
		return $ary;
	}
	
	static function merge($bean1, $bean2, $attr = null)
	{
		$data = $bean2 instanceof Gongo_Bean ? $bean2->_() : (array) $bean2 ;
		foreach ($data as $k => $v) {
			if (is_null($attr) || in_array($k, $attr)) {
				$bean1->{$k} = $v;
			}
		}
		return $bean1;
	}
	
	static function mergeRecursive($bean1, $bean2, $attr = null)
	{
		$data = $bean2 instanceof Gongo_Bean ? $bean2->_() : (array) $bean2 ;
		foreach ($data as $k => $v) {
			if (is_null($attr) || in_array($k, $attr)) {
				if ($v instanceof Gongo_Bean) {
					$bean = $bean1->{$k} instanceof Gongo_Bean ? $bean1->{$k} : Gongo_Locator::get('Gongo_Bean') ;
					$bean1->{$k} = self::mergeRecursive($bean, $v);
				} else {
					$bean1->{$k} = $v;
				}
			}
		}
		return $bean1;
	}

	static function cast($bean, $data, $strict = true, $unset = false)
	{
		$beanData = $bean->_();
		if (empty($beanData)) return self::merge($bean, $data);
		$srcData = $data instanceof Gongo_Bean ? $data->_() : (array) $data ;
		foreach ($srcData as $k => $v) {
			if (array_key_exists($k, $beanData)) {
				$type = $beanData[$k];
				if (is_int($type)) {
					$bean->{$k} = (int) $v;
				} else if (is_float($type)) {
					$bean->{$k} = (float) $v;
				} else if (is_string($type)) {
					$bean->{$k} = (string) $v;
				} else if (is_bool($type)) {
					$bean->{$k} = (bool) $v;
				} else {
					$bean->{$k} = $v;
				}
			} else if (!$strict) {
				$bean->{$k} = $v;
			}
		}
		if ($unset) {
			$beanKey = array_keys($beanData);
			$srcKey = array_keys($srcData);
			foreach (array_diff($beanKey, $srcKey) as $k) {
				unset($bean->{$k});
			}
		}
		return $bean;
	}

	function __construct($ary = array())
	{
		self::import($this, $ary);
	}
	
	public function __get($key)
	{
		return isset($this->_data[$key]) ? $this->_data[$key] : null;
	}

	public function __set($key, $value)
	{
		$this->_data[$key] = $value;
		return $value;
	}
	
	public function __isset($key)
	{
		return isset($this->_data[$key]);
	}
	
	public function __unset($key)
	{
		unset($this->_data[$key]);
	}
	
	public function __call($name, $args)
	{
		$default = array_shift($args);
		$value = $this->{$name};
		return is_null($value) ? $default : $value ;
	}
	
	public function _($ary = null)
	{
		if (is_null($ary)) {
			return $this->_data;
		}
		$this->_data = $ary;
		return $this;
	}

	public function __()
	{
		return $this->_(array());
	}
	
	public function ___($ary = array())
	{
		$className = get_class($this);
		return Gongo_Locator::get($className, $ary);
	}
	
	public function getIterator() 
	{
		return Sloth::iter(new ArrayIterator($this->_data));
	}
}
