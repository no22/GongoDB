<?php
class Gongo_Bean_ArrayWrapper extends Gongo_Bean
{
	public function __construct(&$data = array())
	{
		$this->_data = &$data;
	}

	public function &_($ary = null)
	{
		return $this->_data;
	}
}
