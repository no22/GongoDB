<?php
class Gongo_Db_GoQlException extends Exception {}

class Gongo_Db_GoQL
{
	protected $_query = array();
	protected $_namedScopes = array();
	protected $_clause = array(
		'begin' => array('#', '___setQuery'),
		'select' => array('select', '___addQuery'),
		'insert' => array('insert', '___addEmptyQuery'),
		'into' => array('into', '___addQuery'),
		'update' => array('update', '___addQuery'),
		'delete' => array('delete', '___addEmptyQuery'),
		'from' => array('from', '___addQuery'),
		'values' => array('values', '___addQuery'),
		'set' => array('set', '___addQuery'),
		'join' => array('join', '___addQuery'),
		'innerJoin' => array('innerjoin', '___addQuery'),
		'rawJoin' => array('rawjoin', '___addQuery'),
		'where' => array('where', '___addQuery'),
		'groupBy' => array('groupby', '___addQuery'),
		'having' => array('having', '___addQuery'),
		'orderBy' => array('orderby', '___setQuery'),
		'limit' => array('limit', '___setQuery'),
		'union' => array('union', '___addQuery'),
		'end' => array('%', '___setQuery'),
		'params' => array('params', '___addQuery'),
		'method' => array('method', '___setQuery'),
		'bind' => array('bind', '___bindValue'),
		'entityClass' => array('entityclass', '___setQuery'),
		'fields' => array('fields', '___addQuery'),
		'ifields' => array('ifields', '___addQuery'),
		'strict' => array('strict', '___setQuery'),
		'count' => array('count', '___setQuery'),
	);
	protected $_clauseMap = array();
	protected $_defaultMethod = 'all';
	protected $_returnRowCount = false;
	protected $_ignoreKeys = array();
	protected $_arguments = array();
	protected $_currentParams = null;

	public function __construct()
	{
		$this->___setClauseMap();
	}

	protected function ___setClauseMap()
	{
		$this->_clauseMap = array();
		foreach ($this->_clause as $k => $v) {
			$this->_clauseMap[$v[0]] = $k;
		}
	}

	public function namedScopes($aNamedScopes = null)
	{
		if (is_null($aNamedScopes)) return $this->_namedScopes;
		$this->_namedScopes = $aNamedScopes;
		return $this;
	}

	public function namedScope($alias, $query)
	{
		$this->_namedScopes[$alias] = $query;
		return $this;
	}

	public function rowCount($returnRowCount = true)
	{
		$this->_returnRowCount = $returnRowCount;
		return $this;
	}

	public function ignoreKeys($ignoreKeys = null)
	{
		if (is_null($ignoreKeys)) return $this->_ignoreKeys;
		$this->_ignoreKeys = $ignoreKeys;
		return $this;
	}

	public function addIgnoreKey($key)
	{
		$this->_ignoreKeys[] = $key;
		return $this;
	}

	public function __get($key)
	{
		if (isset($this->_namedScopes[$key])) {
			return $this->setQuery($this->_namedScopes[$key]);
		}
		throw Gongo_Locator::get('Gongo_Db_GoQlException', "{$key} was not found in namedScopes");
	}

	public function __call($sName, $aArgs)
	{
		if (isset($this->_clause[$sName])) {
			return call_user_func(array($this, $this->_clause[$sName][1]), $this->_clause[$sName][0], $aArgs);
		}
		if (isset($this->_namedScopes[$sName])) {
			$this->setQuery($this->_namedScopes[$sName]);
			$methodName = isset($this->_query['method']) ? $this->_query['method'] : $this->_defaultMethod ;
			$methodName = is_array($methodName) ? $methodName[0] : $methodName ;
			return call_user_func_array(array($this, $methodName), $aArgs);
		}
		if (strpos($sName, '_') === 0) {
			$sName = substr($sName, 1);
			if (isset($this->_namedScopes[$sName])) {
				$query = $this->_namedScopes[$sName];
				$params = isset($query['params']) ? $query['params'] : null ;
				return call_user_func(array($this->setQuery($this->_namedScopes[$sName]), '___bindParams'), $aArgs, $params);
			}
		}
		throw Gongo_Locator::get('Gongo_Db_GoQlException', "{$sName} was not found in GoQL method.");
	}

	public function ___addQuery($clause, $args)
	{
		$args = is_string($args) ? array($args) : $args ;
		foreach ($args as $key => $phrase) {
			if (!is_string($key)) {
				$this->_query[$clause][] = $phrase;
			} else {
				$this->_query[$clause][$key] = $phrase;
			}
		}
		return $this;
	}

	public function ___addEmptyQuery($clause, $args)
	{
		$args = empty($args) ? array('') : $args ;
		return $this->___addQuery($clause, $args);
	}

	public function ___setQuery($clause, $args)
	{
		if (empty($args)) return $this;
		$this->_query[$clause] = $args;
		return $this;
	}

	public function setQuery($aQuery)
	{
		foreach ($aQuery as $clause => $phrase) {
			if (!is_string($clause) && is_string($phrase)) {
				$this->__get($phrase);
			} else if (isset($this->_clauseMap[$clause])) {
				$this->__call($this->_clauseMap[$clause], $phrase);
			}
		}
		return $this;
	}

	public function getQuery($clause = null)
	{
		if (is_null($clause)) return $this->_query;
		return isset($this->_query[$clause]) ? $this->_query[$clause] : null ;
	}

	public function ___bindParams($args, $params)
	{
		if (is_null($params)) {
			$arr = array_shift($args);
		} else {
			$params = is_array($params) ? implode(',', $params) : $params ;
			$params = is_string($params) ? array_map('trim', explode(',', $params)) : $params ;
			$arr = array();
			foreach ($args as $i => $arg) {
				$arr[$params[$i]] = $arg;
			}
		}
		foreach ($arr as $k => $v) {
			$len = strlen($k);
			if (strpos($k, '#', $len-1) !== false) {
				if (isset($this->_arguments[$k])) {
					$newarr = $this->_arguments[$k];
					$newarr[] = $v;
					$arr[$k] = $newarr;
				} else {
					$arr[$k] = array($v);
				}
			}
		}
		$this->_arguments = array_merge($this->_arguments, $arr);
		return $this;
	}

	public function ___bindValue($clause, $args)
	{
		if (empty($args)) return $this;
		return $this->___bindParams($args, null);
	}
}
