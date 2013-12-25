<?php
class Gongo_Db_Iter implements Iterator
{
	protected $pdo = null;
	protected $statement = null;
	protected $sql = null;
	protected $params = null;
	protected $fetchType = PDO::FETCH_ASSOC;
	protected $strict = false;
	protected $row = null;
	protected $index = 0;
	
	public function __construct(PDO $pdo = null, $query = null, $params = null, $fetchType = null, $strict = null)
	{
		if (!is_null($pdo)) {
			$this->set($pdo);
		}
		if (!is_null($query)) {
			$this->query($query);
		}
		if (!is_null($fetchType)) {
			$this->type($fetchType);
		}
		if (!is_null($strict)) {
			$this->strict($strict);
		}
		if (!is_null($params)) {
			$this->params($params);
		}
	}
	
	public function set(PDO $pdo)
	{
		$this->pdo = $pdo;
		return $this;
	}

	public function query($query)
	{
		$this->sql = $query;
		return $this;
	}

	public function type($fetchType)
	{
		$this->fetchType = $fetchType;
		return $this;
	}

	public function strict($strict)
	{
		$this->strict = $strict;
		return $this;
	}

	public function params($params)
	{
		$this->params = $params;
		return $this;
	}
	
	public function rewind()
	{
		if (is_null($this->statement)) {
			if (!$this->params) {
				$this->statement = $this->pdo->query($this->sql);
			} else {
				$this->statement = $this->pdo->prepare($this->sql);
				$this->execute($this->statement, $this->params, $this->strict);
			}
		}
		$this->row = $this->statement->fetch($this->fetchType, 0);
		$this->index = 0;
	}

	public function next()
	{
		$this->row = $this->statement->fetch($this->fetchType);
		$this->index++;
	}

	public function valid()
	{
		if ($this->row === false) {
			$this->statement = null;
			return false;
		}
		return true;
	}

	public function current()
	{
		return $this->row;
	}
	
	public function key() {
		return $this->index;
	}

	protected function execute($st, $params, $strict = false)
	{
		if (!$strict) return $st->execute($params);
		foreach ($params as $k => $v) {
			$k = is_int($k) ? $k+1 : $k ;
			$st->bindValue($k, $v, $this->bindType($k, $v));
		}
		return $st->execute();
	}

	protected function bindType($k, $v)
	{
		if (is_int($v)) return PDO::PARAM_INT;
		if (is_bool($v)) return PDO::PARAM_BOOL;
		if (is_null($v)) return PDO::PARAM_NULL;
		return PDO::PARAM_STR;
	}
}
