<?php
class Gongo_Db_Iter implements Iterator
{
	protected $pdo = null;
	protected $statement = null;
	protected $sql = null;
	protected $params = null;
	protected $fetchType = PDO::FETCH_ASSOC;
	protected $row = null;
	protected $index = 0;
	
	public function __construct(PDO $pdo = null, $query = null, $params = null, $fetchType = null)
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
				$this->statement->execute($this->params);
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
}
