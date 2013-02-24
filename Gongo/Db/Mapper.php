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
	public $relation = array();
	protected $defaultTableAlias = 't';
	protected $joinMapper = null;
	
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

	function relation($value = null)
	{
		if (is_null($value)) return $this->relation;
		$this->relation = $value;
		return $this;
	}
	
	function addRelation($value)
	{
		$this->relation[] = $value;
		return $this;
	}

	function currentDateTime()
	{
		return date('Y-m-d H:i:s');
	}

	function joinMapper($value = null)
	{
		if (is_null($value)) return $this->joinMapper;
		$this->joinMapper = $value;
		return $this;
	}
	
	function finder($fields = null, $inner = false) { return $this->query($fields, $inner); }
	function q($fields = null, $inner = false) { return $this->query($fields, $inner); }
	function select($fields = null, $inner = false) { return $this->query($fields, $inner); }

	function query($fields = null, $inner = false, $q = null)
	{
		$q = is_null($q) ? Gongo_Locator::get('Gongo_Db_QueryBuilder', $this) : $q ;
		return $this->_prepareFields($q, $fields, $inner);
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

	function _prepareFields($q, $fields, $inner = false) 
	{
		if (is_null($fields)) return $q;
		$join = array();
		$select = array();
		foreach ($fields as $k => $v) {
			if (strpos($v, '.') === false) $v = $this->defaultTableAlias . "." . $v;
			list($table, $col) = explode('.', $v);
			if ($table !== $this->defaultTableAlias) {
				$join[] = $table;
			}
			$column = $this->identifier($table) . '.' . $this->identifier($col);
			if (!is_int($k)) $column .= ' AS ' . $this->identifier($k);
			$select[] = $column;
		}
		$joinMapper = $this->joinMapper();
		if (is_null($joinMapper)) {
			$q = $this->join($join, $q, $inner);
		} else {
			$q = $joinMapper->join($join, $q, $inner);
		}
		$q->select(implode(', ', $select));
		return $q;
	}
	
	function _prepareQuery($q, $query) 
	{
		if (isset($query['fields']) && !empty($query['fields'])) {
			$this->_prepareFields($q, $query['fields'], false);
		}
		if (isset($query['ifields']) && !empty($query['ifields'])) {
			$this->_prepareFields($q, $query['ifields'], true);
		}
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
		if ($name === '*') return $name;
		return $this->quote . $name . $this->quote;
	}

	protected function makeColumnLabel($bean, $eq = false, $ignore = null, $want = null)
	{
		$col = array();
		$param = array();
		$var = array();
		foreach ($bean as $k => $v) {
			if (is_null($ignore) || !in_array($k, $ignore)) {
				$id = $this->identifier($k);
				$col[] = $id . ($eq ? "=:{$k}" : '');
				$param[':' . $k] = $v;
				$var[] = ':' . $k;
			} else if (!is_null($want) && in_array($k, $want)) {
				$param[':' . $k] = $v;
			}
		}
		return array($col, $param, $var);
	}
	
	function insert($bean, $q = null)
	{
		if ($this->autoPopulate()) {
			$current = $this->currentDateTime();
			$bean->{$this->createdDateColumn()} = $current;
			$bean->{$this->modifiedDateColumn()} = $current;
		}
		$q = is_null($q) ? $this->query() : $q ;
		$ignoreKeys = $q->ignoreKeys();
		$wantKeys = $q->wantKeys();
		list($col, $param, $var) = $this->makeColumnLabel($bean, false, $ignoreKeys, $wantKeys);
		$result = $q->insert()->into($this->tableName() . ' (' . implode(',', $col) .')')->values('(' . implode(',', $var) .')')->exec($param);
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
		$wantKeys = array($pk);
		$wantKeys = array_merge($wantKeys, $q->wantKeys());
		list($set, $param, $var) = $this->makeColumnLabel($bean, true, $ignoreKeys, $wantKeys);
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
			return $q->delete()->from($this->tableName())->where("{$pkid} = :{$pk}")->rowCount($returnRowCount)->exec(array(":{$pk}" => $id));
		}
		$q = is_null($q) ? $this->query() : $q ;
		list($set, $param, $var) = $this->makeColumnLabel($id, true, $q->ignoreKeys(), $q->wantKeys());
		return $q->delete()->from($this->tableName())->where($set)->rowCount($returnRowCount)->exec($param);
	}

	function get($id = null, $q = null, $empty = false)
	{
		if (!$id) return $this->bean();
		$pk = $this->primaryKey();
		$q = is_null($q) ? $this->query() : $q ;
		$pkid = $this->identifier($pk);
		$bean = $q->select('*')->from($this->tableName())->where("{$pkid} = :{$pk}")->first(array(":{$pk}" => $id));
		if (!$empty) return $bean;
		return $bean ? $bean : $this->bean() ;
	}

	function getRelationMapper($key) 
	{
		if (!isset($this->relation[$key])) return null;
		$mapper = $this->relation[$key];
		if (!is_string($mapper)) return $mapper;
		$this->relation[$key] = Gongo_Locator::get($mapper);
		return $this->relation[$key];
	}

	function foreignKey($key)
	{
		return $key . '_id';
	}

	function join($keys, $q = null, $inner = false) 
	{
		$q = is_null($q) ? $this->query() : $q ;
		$fromTable = $this->identifier($this->table());
		$fromAlias = $this->identifier($this->defaultTableAlias);
		$q->from($fromTable . " AS {$fromAlias}");
		foreach ($keys as $key => $obj) {
			if (is_int($key)) {
				$relMapper = $this->getRelationMapper($obj);
				$key = $obj;
			} else {
				if (is_string($obj)) {
					$obj = Gongo_Locator::get($obj);
				}
				$relMapper = $obj;
			}
			$joinTable = $this->identifier($relMapper->table());
			$joinAlias = $this->identifier($key);
			$pk = $this->identifier($relMapper->primaryKey());
			$fkey = $this->identifier($this->foreignKey($key));
			if ($inner) {
				$q->innerJoin("{$joinTable} AS {$joinAlias} ON {$fromAlias}.{$fkey} = {$joinAlias}.{$pk}");
			} else {
				$q->join("{$joinTable} AS {$joinAlias} ON {$fromAlias}.{$fkey} = {$joinAlias}.{$pk}");
			}
		}
		return $q;
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
