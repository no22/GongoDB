<?php
class Gongo_Db_Mapper
{
	protected $db;
	protected $table;
	protected $queryWriter;
	protected $pk = 'id';
	protected $entityClass = 'Gongo_Bean';
	protected $namedScopes = array();
	protected $autoPopulate = true;
	protected $createdDateColumn = 'created';
	protected $modifiedDateColumn = 'modified';
	protected $quote = '`';
	
	function __construct($db = null, $table = null, $pk = null, $namedScopes = null, $queryWriter = null)
	{
		if (!is_null($db)) $this->db($db);
		if (!is_null($table)) $this->table($table);
		if (!is_null($pk)) $this->primaryKey($pk);
		if (!is_null($namedScopes)) $this->namedScopes($namedScopes);
		$queryWriter = is_null($queryWriter) ? Gongo_Locator::get('Gongo_Db_QueryWriter') : $queryWriter ;
		$this->queryWriter($queryWriter);
	}
	
	function db($value = null)
	{
		if (is_null($value)) return $this->db;
		$this->db = $value;
		return $this;
	}
	
	function table($value = null)
	{
		if (is_null($value)) return $this->table;
		$this->table = $value;
		return $this;
	}
	
	function tableName()
	{
		return $this->db()->tableName($this->table());
	}

	function primaryKey($value = null)
	{
		if (is_null($value)) return $this->pk;
		$this->pk = $value;
		return $this;
	}

	function entityClass($value = null)
	{
		if (is_null($value)) return $this->entityClass;
		$this->entityClass = $value;
		return $this;
	}

	function namedScopes($value = null)
	{
		if (is_null($value)) return $this->namedScopes;
		$this->namedScopes = $value;
		return $this;
	}

	function queryWriter($value = null)
	{
		if (is_null($value)) return $this->queryWriter;
		$this->queryWriter = $value;
		return $this;
	}

	function autoPopulate($value = null)
	{
		if (is_null($value)) return $this->autoPopulate;
		$this->autoPopulate = $value;
		return $this;
	}

	function createdDateColumn($value =null)
	{
		if (is_null($value)) return $this->createdDateColumn;
		$this->createdDateColumn = $value;
		return $this;
	}

	function modifiedDateColumn($value = null)
	{
		if (is_null($value)) return $this->modifiedDateColumn;
		$this->modifiedDateColumn = $value;
		return $this;
	}

	function currentDateTime()
	{
		return date('Y-m-d H:i:s');
	}
	
	function finder() { return $this->query(); }
	function q() { return $this->query(); }

	function query()
	{
		return Gongo_Locator::get('Gongo_Db_QueryBuilder', $this);
	}
	
	function setFromTable($query)
	{
		if (!isset($query['from'])) {
			$query['from'] = $this->tableName();
		}
		return $query;
	}

	function setSelectColumn($query)
	{
		if (!isset($query['select'])) {
			$query['select'] = '*';
		}
		return $query;
	}

	function bean($ary = array(), $entityClass = null)
	{
		$entityClass = is_null($entityClass) ? $this->entityClass() : $entityClass ;
		return Gongo_Locator::get($entityClass, $ary);
	}
	
	function _replaceTableName($m)
	{
		$table = preg_replace('/\[(.*?)\]/', $this->db()->tablePrefix() . '$1', $m[1]);
		return $table . $m[2];
	}
	
	function replaceTableName($sql)
	{
		if ($this->db()->tablePrefix() === '' || (strpos($sql, '"') !== false && strpos($sql, "'") !== false)) {
			if (strpos($sql, '[') === false) return $sql;
			return preg_replace('/\[(.*?)\]/', $this->db()->tablePrefix() . '$1', $sql);
		}
		return preg_replace_callback(
			'/([^\'"]*)(\'(?:[^\'\\\\]|\\\\\')*?\'|"(?:[^"\\\\]|\\\\")*?"|$)/',
			array($this, '_replaceTableName'), $sql
		);
	}

	function _sql($query, $args = null, $boundParams = array())
	{
		$sql = $this->replaceTableName($this->queryWriter()->build($query, $this->namedScopes()));
		$args = $this->queryWriter()->params($args, $query, $boundParams);
		return array($sql, $args);
	}

	function _all($query, $args = null, $boundParams = array())
	{
		$entityClass = isset($query['entityclass']) ? $query['entityclass'] : null ;
		$prototype = $this->bean(array(), $entityClass);
		return $this->_iter($query, $args, $boundParams)->map(array($prototype, '___'));
	}
	
	function _iter($query, $args = null, $boundParams = array())
	{
		$query = $this->setSelectColumn($query);
		$query = $this->setFromTable($query);
		$sql = $this->replaceTableName($this->queryWriter()->buildSelectQuery($query, $this->namedScopes()));
		$args = $this->queryWriter()->params($args, $query, $boundParams);
		return $this->db()->iter($sql, $args);
	}
	
	function _row($query, $args = null, $boundParams = array())
	{
		$query = $this->setSelectColumn($query);
		$query = $this->setFromTable($query);
		$sql = $this->replaceTableName($this->queryWriter()->buildSelectQuery($query, $this->namedScopes()));
		$args = $this->queryWriter()->params($args, $query, $boundParams);
		return $this->db()->row($sql, $args);
	}

	function _first($query, $args = null, $boundParams = array())
	{
		$result = $this->_row($query, $args, $boundParams);
		if ($result) {
			$entityClass = isset($query['entityclass']) ? $query['entityclass'] : null ;
			return $this->bean($result, $entityClass);
		}
		return null;
	}
	
	function _count($query, $args = null, $boundParams = array())
	{
		$query = $this->setFromTable($query);
		$query['select'] = "count(*) AS count";
		$sql = $this->replaceTableName($this->queryWriter()->buildSelectQuery($query, $this->namedScopes()));
		$args = $this->queryWriter()->params($args, $query, $boundParams);
		$bean = $this->db()->first($sql, $args);
		return $bean ? (int) $bean->count : null ;
	}

	function _exec($query, $args = null, $returnRowCount = false, $boundParams = array())
	{
		$sql = $this->replaceTableName($this->queryWriter()->build($query, $this->namedScopes()));
		$args = $this->queryWriter()->params($args, $query, $boundParams);
		return $this->db()->exec($sql, $args, $returnRowCount);
	}
	
	function lastInsertId()
	{
		return (int) $this->db()->lastInsertId($this->primaryKey());
	}

	function identifier($name)
	{
		return $this->quote . $name . $this->quote;
	}

	protected function makeColumnLabel($bean, $eq = false, $ignore = null)
	{
		$col = array();
		$param = array();
		foreach ($bean as $k => $v) {
			if (is_null($ignore) || !in_array($k, $ignore)) {
				$id = $this->identifier($k);
				$col[] = $id . ($eq ? "=:{$k}" : '');
			}
			$param[':' . $k] = $v;
		}
		return array($col, $param);
	}
	
	function insert($bean, $q = null)
	{
		if ($this->autoPopulate()) {
			$current = $this->currentDateTime();
			$bean->{$this->createdDateColumn()} = $current;
			$bean->{$this->modifiedDateColumn()} = $current;
		}
		$q = is_null($q) ? $this->query() : $q ;
		list($col, $param) = $this->makeColumnLabel($bean);
		$val = array_keys($param);
		$result = $q->insert()->into($this->tableName() . ' (' . implode(',', $col) .')')->values('(' . implode(',', $val) .')')->exec($param);
		if ($result) {
			$bean->{$this->primaryKey()} = $this->lastInsertId();
		}
		return $result;
	}

	function update($bean, $q = null, $returnRowCount = false)
	{
		if ($this->autoPopulate()) {
			$current = $this->currentDateTime();
			$bean->{$this->modifiedDateColumn()} = $current;
		}
		$q = is_null($q) ? $this->query() : $q ;
		$set = array();
		$param = array();
		$pk = $this->primaryKey();
		$ignoreKeys = array($pk);
		$ignoreKeys = array_merge($ignoreKeys, $q->ignoreKeys());
		list($set, $param) = $this->makeColumnLabel($bean, true, $ignoreKeys);
		$pkid = $this->identifier($pk);
		return $q->update($this->tableName())->set(implode(',', $set))->where("{$pkid} = :{$pk}")->rowCount($returnRowCount)->exec($param);
	}
	
	function save($bean, $q = null)
	{
		$pk = $this->primaryKey();
		if ($bean->{$pk}) {
			return $this->update($bean, $q);
		}
		return $this->insert($bean, $q);
	}

	function delete($id, $q = null, $returnRowCount = false)
	{
		if (is_int($id) || is_numeric($id)) {
			$pk = $this->primaryKey();
			$q = is_null($q) ? $this->query() : $q ;
			$pkid = $this->identifier($pk);
			return $q->delete()->from($this->tableName())->where("{$pkid} = :{$pk}")->rowCount($returnRowCount)->exec(array(":{$pk}" => (int) $id));
		}
		list($set, $param) = $this->columnList($bean, true);
		$q = is_null($q) ? $this->query() : $q ;
		return $q->delete()->from($this->tableName())->where($set)->rowCount($returnRowCount)->exec($param);
	}

	function get($id = null, $q = null)
	{
		if (!$id) return $this->bean();
		$pk = $this->primaryKey();
		$q = is_null($q) ? $this->query() : $q ;
		$pkid = $this->identifier($pk);
		return $q->select('*')->from($this->tableName())->where("{$pkid} = :{$pk}")->first(array(":{$pk}" => $id));
	}

	function beginTransaction()
	{
		return $this->db()->beginTransaction();
	}
	
	function commit()
	{
		return $this->db()->commit();
	}
	
	function rollBack()
	{
		return $this->db()->rollBack();
	}

	function pdo()
	{
		return $this->db()->pdo();
	}
}
