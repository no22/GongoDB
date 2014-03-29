<?php
class Gongo_Db
{
	static protected $_defaultLog;
	protected $_db;
	protected $_log;
	protected $_inClause;
	protected $_currentParams;
	protected $_classPrefix = 'Mapper_';
	protected $_tablePrefix = '';
	protected $_entityClass = 'Gongo_Bean';
	protected $_iteratorClass = 'Gongo_Db_Iter';
	protected $_strict = false;

	static function setLog($log = null)
	{
		self::$_defaultLog = is_null($log) ? Gongo_Locator::get('Gongo_Log', 'sqllog.txt') : $log ;
	}

	static function like($text, $e = null)
	{
		if (is_null($e)) return strtr($text, array('%' => '\%', '_' => '\_'));
		return strtr($text, array($e => $e . $e, '%' => $e . '%', '_' => $e . '_'));
	}

	function __construct($db = null, $log = null)
	{
		$this->_log = is_null($log) ? self::$_defaultLog : $log ;
		$this->pdo($db);
	}

	function pdo($pdo = null)
	{
		if (is_null($pdo)) return $this->_db;
		$this->_db = $pdo;
		return $this;
	}

	function setQueryLog($log)
	{
		$this->_log = $log;
	}

	protected function log($sql, $params)
	{
		if (!is_null($this->_log)) {
			$this->_log->add(
				get_class($this) . ' : ' . print_r(
					array(
						'sql' => $sql, 'params' => $params,
					), true
				)
			);
		}
	}

	function writeLog($text, $email = null)
	{
		$this->_log->add($text, $email);
	}

	function prepareSql($sql, $params)
	{
		if (is_null($params)) {
			return array($sql, $params);
		}
		$this->_inClause = array();
		$this->_currentParams = $params;

		$sql = preg_replace_callback('/IN\s*\((:\w+)\)/',
			array($this, '_expandInClauseCallback'), $sql
		);

		foreach ($this->_inClause as $k => $v) {
			unset($params[$k]);
			$params = $params + $v;
		}
		return array($sql, $params);
	}

	function _expandInClauseCallback($matches)
	{
		$key = $matches[1];
		if (array_key_exists($key, $this->_currentParams)) {
			$params = $this->_currentParams[$key];
			$params = !$params ? '' : $params ;
			$params = is_string($params) ? explode(',', $params) : $params ;
			foreach ($params as $k => $v) {
				$this->_inClause[$key][$key . '__' . $k] = $v;
			}
			return 'IN (' . implode(',', array_keys($this->_inClause[$key])) . ')';
		}
		return $matches[0];
	}

	function entityClass($value = null)
	{
		if (is_null($value)) return $this->_entityClass;
		$this->_entityClass = $value;
		return $this;
	}

	function iteratorClass($value = null)
	{
		if (is_null($value)) return $this->_iteratorClass;
		$this->_iteratorClass = $value;
		return $this;
	}

	function strict($value = null)
	{
		if (is_null($value)) return $this->_strict;
		$this->_strict = $value;
		return $this;
	}

	function iter($sql, $params = null, $strict = null)
	{
		$strict = is_null($strict) ? $this->strict() : $strict ;
		list($sql, $params) = $this->prepareSql($sql, $params);
		$this->log($sql, $params);
		return Sloth::iter(Gongo_Locator::get($this->iteratorClass(), $this->pdo(), $sql, $params, PDO::FETCH_ASSOC, $strict));
	}

	function bean($ary = array())
	{
		return Gongo_Locator::get($this->entityClass(), $ary);
	}

	function all($sql, $params = null, $strict = null)
	{
		return $this->iter($sql, $params, $strict)->map(array($this, 'bean'));
	}

	function row($sql, $params = null, $strict = null)
	{
		list($sql, $params) = $this->prepareSql($sql, $params);
		$this->log($sql, $params);
		$st = $this->pdo()->prepare($sql);
		$result = $this->_execute($st, $params, $strict);
		if ($result) {
			$row = $st->fetch(PDO::FETCH_ASSOC);
			return $row;
		}
	}

	function first($sql, $params = null, $strict = null)
	{
		$result = $this->row($sql, $params, $strict);
		if ($result) {
			return $this->bean($result);
		}
		return null;
	}

	function exec($sql, $params = null, $returnRowCount = false, $strict = null)
	{
		list($sql, $params) = $this->prepareSql($sql, $params);
		$this->log($sql, $params);
		$st = $this->pdo()->prepare($sql);
		$result = $this->_execute($st, $params, $strict);
		if ($result && $returnRowCount) {
			return $st->rowCount();
		}
		return $result;
	}

	function _bindType($k, $v)
	{
		if (is_int($v)) return PDO::PARAM_INT;
		if (is_bool($v)) return PDO::PARAM_BOOL;
		if (is_null($v)) return PDO::PARAM_NULL;
		return PDO::PARAM_STR;
	}

	function _execute($st, $params, $strict = null)
	{
		$strict = is_null($strict) ? $this->strict() : $strict ;
		if (!$strict) return $st->execute($params);
		foreach ($params as $k => $v) {
			$k = is_int($k) ? $k+1 : $k ;
			$st->bindValue($k, $v, $this->_bindType($k, $v));
		}
		return $st->execute();
	}

	function beginTransaction()
	{
		return $this->pdo()->beginTransaction();
	}

	function commit()
	{
		return $this->pdo()->commit();
	}

	function rollBack()
	{
		return $this->pdo()->rollBack();
	}

	function classPrefix($value = null)
	{
		if (is_null($value)) return $this->_classPrefix;
		$this->_classPrefix = $value;
		return $this;
	}

	function className($name)
	{
		return $this->_classPrefix . $name ;
	}

	function tablePrefix($value = null)
	{
		if (is_null($value)) return $this->_tablePrefix;
		$this->_tablePrefix = $value;
		return $this;
	}

	function tableName($name)
	{
		if ($name == '') return false;
		return $this->_tablePrefix . $name ;
	}

	function lastInsertId($col = null)
	{
		return (int) $this->pdo()->lastInsertId($col);
	}

	function __get($name)
	{
		$className = $this->className($name);
		return Gongo_Locator::get($className, $this);
	}
}
