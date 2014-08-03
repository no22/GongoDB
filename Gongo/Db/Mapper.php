<?php
class Gongo_Db_Mapper
{
	protected $db;
	protected $table;
	protected $queryWriter;
	protected $pk = 'id';
	protected $entityClass = 'Gongo_Bean';
	public $namedScopes = array();
	protected $autoPopulate = true;
	protected $createdDateColumn = 'created';
	protected $modifiedDateColumn = 'modified';
	protected $quote = '`';
	public $relation = array();
	protected $defaultTableAlias = 't';
	protected $joinMapper = null;
	protected $currentArgs = array();
	protected $argsCount = array();
	protected $strict = null;
	protected $tableAlias = array();

	static function joinHandler($self, $keys, $q = null, $inner = false)
	{
		$q = is_null($q) ? $self->query() : $q ;
		$fromTable = $self->identifier($self->tableName());
		$fromAlias = $self->identifier($self->defaultTableAlias());
		$from = $q->getQuery('from');
		if (empty($from)) {
			$q->from($fromTable . " AS {$fromAlias}");
		}
		foreach ($keys as $key => $obj) {
			if (is_int($key)) {
				$relMapper = $self->getRelationMapper($obj);
				$key = $obj;
			} else {
				if (is_string($obj)) {
					$obj = $self->getRelationMapperInstance($obj);
				}
				$relMapper = $obj;
			}
			if ($relMapper) {
				$joinTable = $self->identifier($relMapper->tableName());
				$joinAlias = $self->identifier($key);
				$pk = $self->pkId($relMapper);
				$fkey = $self->identifier($self->foreignKey($key));
				if ($inner) {
					$q->innerJoin("{$joinTable} AS {$joinAlias} ON {$fromAlias}.{$fkey} = {$joinAlias}.{$pk}");
				} else {
					$q->join("{$joinTable} AS {$joinAlias} ON {$fromAlias}.{$fkey} = {$joinAlias}.{$pk}");
				}
			}
		}
		return $q;
	}

	function __construct($db = null, $table = null, $pk = null, $namedScopes = null, $queryWriter = null)
	{
		if (!is_null($db)) $this->db($db);
		if (!is_null($table)) $this->table($table);
		if (!is_null($pk)) $this->primaryKey($pk);
		$inheritedNamedScopes = $this->inheritNamedScopes();
		if (!is_null($namedScopes)) {
			$inheritedNamedScopes = array_merge($inheritedNamedScopes, $namedScopes);
		}
		$this->namedScopes($inheritedNamedScopes);
		$queryWriter = is_null($queryWriter) ? Gongo_Locator::get('Gongo_Db_QueryWriter') : $queryWriter ;
		$this->queryWriter($queryWriter);
		$this->setQueryWriterDefaultTableName();
	}

	public function inheritNamedScopes($sClass = null)
	{
		$sClass = is_null($sClass) ? get_class($this) : $sClass ;
		$aVars = get_class_vars($sClass);
		$aNamedScopes = isset($aVars['namedScopes']) ? $aVars['namedScopes'] : array() ;
		$sParent = get_parent_class($sClass);
		if (!$sParent) return $aNamedScopes;
		$aParentNamedScopes = $this->inheritNamedScopes($sParent);
		return array_merge($aParentNamedScopes, $aNamedScopes);
	}

	function setQueryWriterDefaultTableName()
	{
		$db = $this->db();
		if (!is_null($db) && !$this->table()) {
			$this->queryWriter()->defaultTable($this->tableName());
		}
	}

	function db($value = null)
	{
		if (is_null($value)) return $this->db;
		$this->db = $value;
		$this->setQueryWriterDefaultTableName();
		return $this;
	}

	function table($value = null)
	{
		if (is_null($value)) return $this->table;
		$this->table = $value;
		$this->setQueryWriterDefaultTableName();
		return $this;
	}

	function tableAlias($value = null)
	{
		if (is_null($value)) return $this->tableAlias;
		$this->tableAlias = $value;
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

	function strict($value = null)
	{
		if (is_null($value)) return $this->strict;
		$this->strict = $value;
		return $this;
	}

	function defaultTableAlias($value = null)
	{
		if (is_null($value)) return $this->defaultTableAlias;
		$this->defaultTableAlias = $value;
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
		if (!isset($query['from'])&&!isset($query['union'])&&!isset($query['unionall'])) {
			$tableName = $this->tableName();
			if ($tableName) {
				$query['from'] = $tableName;
			}
		}
		return $query;
	}

	function setSelectColumn($query)
	{
		if (!isset($query['select'])&&!isset($query['union'])&&!isset($query['unionall'])) {
			$query['select'] = '*';
		}
		return $query;
	}

	function bean($ary = array(), $entityClass = null)
	{
		$entityClass = is_null($entityClass) ? $this->entityClass() : $entityClass ;
		return Gongo_Locator::get($entityClass, $ary);
	}

	function _replaceTableAliasCallback($m)
	{
		$tableName = isset($this->tableAlias[$m[1]]) ? $this->tableAlias[$m[1]] : $m[1] ;
		return $this->db()->tablePrefix() . $tableName;
	}

	function _replaceTableAlias($str)
	{
		if (!$this->tableAlias) return preg_replace('/\[(.*?)\]/', $this->db()->tablePrefix() . '$1', $str);
		return preg_replace_callback(
			'/\[(.*?)\]/', array($this, '_replaceTableAliasCallback'), $str
		);
	}

	function _replaceTableName($m)
	{
		$table = $this->_replaceTableAlias($m[1]);
		return $table . $m[2];
	}

	function replaceTableName($sql)
	{
		if ($this->db()->tablePrefix() === '' || (strpos($sql, '"') !== false && strpos($sql, "'") !== false)) {
			if (strpos($sql, '[') === false) return $sql;
			return $this->_replaceTableAlias($sql);
		}
		return preg_replace_callback(
			'/([^\'"]*)(\'(?:[^\'\\\\]|\\\\\')*?\'|"(?:[^"\\\\]|\\\\")*?"|$)/',
			array($this, '_replaceTableName'), $sql
		);
	}

	function _replaceParams($m)
	{
		$key = $m[1];
		if (!isset($this->argsCount[$key])) {
			$this->argsCount[$key] = 1;
			if (isset($this->currentArgs[$key . '#'])) {
				$this->currentArgs[$key] = $this->currentArgs[$key . '#'][0];
			}
			return $m[0];
		}
		$c = $this->argsCount[$key]++;
		$arg = null;
		if (isset($this->currentArgs[$key . '#'])) {
			$arg = $this->currentArgs[$key . '#'][$c];
		} else if (isset($this->currentArgs[$key])) {
			$arg = $this->currentArgs[$key];
		}
		$name = $key . '___' . $c;
		$this->currentArgs[$name] = $arg;
		return $name;
	}

	function _replaceRepeatedParams($m)
	{
		$sql = preg_replace_callback(
			'/(:\w+)/', array($this, '_replaceParams'), $m[1]
		);
		return $sql . $m[2];
	}

	function replaceRepeatedParams($sql, $args)
	{
		$this->currentArgs = $args;
		$this->argsCount = array();
		$sql = preg_replace_callback(
			'/([^\'"]*)(\'(?:[^\'\\\\]|\\\\\')*?\'|"(?:[^"\\\\]|\\\\")*?"|$)/',
			array($this, '_replaceRepeatedParams'), $sql
		);
		foreach ($this->currentArgs as $k => $v) {
			if (strpos($k, '#', strlen($k) - 1) !== false) unset($this->currentArgs[$k]);
		}
		$unusedArgs = array_diff_key($args, $this->argsCount);
		$currentArgs = array_diff_key($this->currentArgs, $unusedArgs);
		return array($sql, $currentArgs);
	}

	function _prepareFields($q, $fields, $inner = false)
	{
		if (is_null($fields)) return $q;
		$join = array();
		$select = array();
		foreach ($fields as $k => $v) {
			$distinct = false;
			if (is_int($k)) {
				$pos = stripos($v, ' AS ');
				if ($pos !== false) {
					$k = trim(substr($v, $pos+4));
					$v = trim(substr($v, 0, $pos));
				}
			}
			$pos = stripos($v, 'DISTINCT ');
			if ($pos !== false) {
				$v = trim(substr($v, $pos+9));
				$distinct = true;
			}
			if (strpos($v, '.') === false) $v = $this->defaultTableAlias . "." . $v;
			list($table, $col) = explode('.', $v);
			if ($table !== $this->defaultTableAlias && !in_array($table, $join)) {
				$join[] = $table;
			}
			$column = ($distinct ? 'DISTINCT ' : '') . $this->identifier($table) . '.' . $this->identifier($col);
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

	function _sql($query, $args = null, $boundParams = array(), $select = false)
	{
		if ($select) {
			$sql = $this->replaceTableName($this->queryWriter()->buildSelectQuery($query, $this->namedScopes()));
		} else {
			$sql = $this->replaceTableName($this->queryWriter()->build($query, $this->namedScopes()));
		}
		$args = $this->queryWriter()->params($args, $query, $boundParams);
		return $this->replaceRepeatedParams($sql, $args);
	}

	function _all($query, $args = null, $boundParams = array(), $strict = null)
	{
		$entityClass = isset($query['entityclass']) ? $query['entityclass'] : null ;
		$prototype = $this->bean(array(), $entityClass);
		return $this->_iter($query, $args, $boundParams, $strict)->map(array($prototype, '___'));
	}

	function _iter($query, $args = null, $boundParams = array(), $strict = null)
	{
		$strict = is_null($strict) ? $this->strict() : $strict ;
		$query = $this->setSelectColumn($query);
		$query = $this->setFromTable($query);
		list($sql, $args) = $this->_sql($query, $args, $boundParams, true);
		return $this->db()->iter($sql, $args, $strict);
	}

	function _row($query, $args = null, $boundParams = array(), $strict = null)
	{
		$strict = is_null($strict) ? $this->strict() : $strict ;
		$query = $this->setSelectColumn($query);
		$query = $this->setFromTable($query);
		list($sql, $args) = $this->_sql($query, $args, $boundParams, true);
		return $this->db()->row($sql, $args, $strict);
	}

	function _first($query, $args = null, $boundParams = array(), $strict = null)
	{
		$result = $this->_row($query, $args, $boundParams, $strict);
		if ($result) {
			$entityClass = isset($query['entityclass']) ? $query['entityclass'] : null ;
			return $this->bean($result, $entityClass);
		}
		return null;
	}

	function _count($query, $args = null, $boundParams = array(), $strict = null)
	{
		$strict = is_null($strict) ? $this->strict() : $strict ;
		$query = $this->setFromTable($query);
		if (isset($query['count'])) {
			if (is_array($query['count'])) {
				foreach ($query['count'] as $k => $v) {
					$query[$k] = $v;
				}
			} else {
				$query['select'] = $query['count'];
			}
		} else {
			$query['select'] = "count(*) AS count";
		}
		list($sql, $args) = $this->_sql($query, $args, $boundParams, true);
		$bean = $this->db()->first($sql, $args, $strict);
		return $bean ? (int) $bean->count : null ;
	}

	function _exec($query, $args = null, $returnRowCount = false, $boundParams = array(), $strict = null)
	{
		$strict = is_null($strict) ? $this->strict() : $strict ;
		list($sql, $args) = $this->_sql($query, $args, $boundParams, false);
		return $this->db()->exec($sql, $args, $returnRowCount, $strict);
	}

	function lastInsertId()
	{
		return (int) $this->db()->lastInsertId($this->primaryKey());
	}

	function identifier($name)
	{
		if ($name === '*') return $name;
		$q = $this->quote;
		return $q . str_replace($q, $q.$q, $name) . $q;
	}

	protected function makeColumnLabel($bean, $eq = false, $ignore = null)
	{
		$col = array();
		$param = array();
		$var = array();
		foreach ($bean as $k => $v) {
			if (is_null($ignore) || !in_array($k, $ignore)) {
				$id = $this->identifier($k);
				$col[] = $id . ($eq ? "=:{$k}" : '');
				$var[] = ':' . $k;
			}
			$param[':' . $k] = $v;
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
		list($col, $param, $var) = $this->makeColumnLabel($bean, false, $q->ignoreKeys());
		$result =
			$q->insert()->into($this->tableName() . ' (' . implode(',', $col) .')')
			->values('(' . implode(',', $var) .')')->exec($param);
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
		list($set, $param, $var) = $this->makeColumnLabel($bean, true, $ignoreKeys);
		$pkid = $this->identifier($pk);
		return
			$q->update($this->tableName())->set(implode(',', $set))
			->where("{$pkid} = :{$pk}")->rowCount($returnRowCount)->exec($param);
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
		if (is_int($id) || is_numeric($id) || is_string($id)) {
			$pk = $this->primaryKey();
			$q = is_null($q) ? $this->query() : $q ;
			$pkid = $this->identifier($pk);
			return
				$q->delete()->from($this->tableName())->where("{$pkid} = :{$pk}")
				->rowCount($returnRowCount)->exec(array(":{$pk}" => $id));
		}
		$q = is_null($q) ? $this->query() : $q ;
		list($set, $param, $var) = $this->makeColumnLabel($id, true, $q->ignoreKeys());
		return
			$q->delete()->from($this->tableName())->where($set)
			->rowCount($returnRowCount)->exec($param);
	}

	function get($id = null, $q = null, $empty = false)
	{
		if (!$id) return $this->bean();
		if (is_int($id) || is_numeric($id) || is_string($id)) {
			$pk = $this->primaryKey();
			$q = is_null($q) ? $this->query() : $q ;
			$pkid = $this->identifier($pk);
			$bean =
				$q->select('*')->from($this->tableName())
				->where("{$pkid} = :{$pk}")->first(array(":{$pk}" => $id));
			if (!$empty) return $bean;
			return $bean ? $bean : $this->bean() ;
		}
		$q = is_null($q) ? $this->query() : $q ;
		list($set, $param, $var) = $this->makeColumnLabel($id, true, $q->ignoreKeys());
		return $q->from($this->tableName())->where($set)->first($param);
	}

	function getRelationMapper($key)
	{
		if (!isset($this->relation[$key])) return null;
		$mapper = $this->relation[$key];
		if (!is_string($mapper)) return $mapper;
		$this->relation[$key] = Gongo_Locator::get($mapper, $this->db());
		return $this->relation[$key];
	}

	function getRelationMapperInstance($class)
	{
		return Gongo_Locator::get($class, $this->db());
	}

	function foreignKey($key)
	{
		return $key . '_id';
	}

	function pkId($relMapper)
	{
		return $this->identifier($relMapper->primaryKey());
	}

	function join($keys, $q = null, $inner = false)
	{
		return self::joinHandler($this, $keys, $q, $inner);
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
