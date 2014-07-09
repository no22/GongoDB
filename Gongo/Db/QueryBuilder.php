<?php
class Gongo_Db_QueryBuilder extends Gongo_Db_GoQL
{
	protected $_defaultMethod = 'all';
	protected $_collection;

	public function __construct($collection)
	{
		$this->_collection = $collection;
		$this->namedScopes($collection->namedScopes());
		parent::__construct();
	}

	public function iter()
	{
		$args = func_get_args();
		$this->_collection->_prepareQuery($this, $this->_query);
		$strict = isset($this->_query['strict']) ? $this->_query['strict'] : null ;
		return $this->_collection->_iter($this->_query, $args, $this->_arguments, $strict);
	}

	public function all()
	{
		$args = func_get_args();
		$this->_collection->_prepareQuery($this, $this->_query);
		$strict = isset($this->_query['strict']) ? $this->_query['strict'] : null ;
		return $this->_collection->_all($this->_query, $args, $this->_arguments, $strict);
	}

	public function row()
	{
		$args = func_get_args();
		$this->_collection->_prepareQuery($this, $this->_query);
		$strict = isset($this->_query['strict']) ? $this->_query['strict'] : null ;
		return $this->_collection->_row($this->_query, $args, $this->_arguments, $strict);
	}

	public function first()
	{
		$args = func_get_args();
		$this->_collection->_prepareQuery($this, $this->_query);
		$strict = isset($this->_query['strict']) ? $this->_query['strict'] : null ;
		return $this->_collection->_first($this->_query, $args, $this->_arguments, $strict);
	}

	public function count()
	{
		$args = func_get_args();
		$this->_collection->_prepareQuery($this, $this->_query);
		$strict = isset($this->_query['strict']) ? $this->_query['strict'] : null ;
		return $this->_collection->_count($this->_query, $args, $this->_arguments, $strict);
	}

	public function exec()
	{
		$args = func_get_args();
		$this->_collection->_prepareQuery($this, $this->_query);
		$strict = isset($this->_query['strict']) ? $this->_query['strict'] : null ;
		return $this->_collection->_exec($this->_query, $args, $this->_returnRowCount, $this->_arguments, $strict);
	}

	public function sql()
	{
		$args = func_get_args();
		$this->_collection->_prepareQuery($this, $this->_query);
		return $this->_collection->_sql($this->_query, $args, $this->_arguments);
	}
}