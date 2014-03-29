<?php
class Gongo_Db_QueryWriter
{
	protected $defaultBuilder = 'Gongo_Db_GoQL';
	protected $namedScopes = array();
	protected $defaultTable = '';

	protected $operator = array(
		'$or' => 'OR',
		'$and' => 'AND',
		'$not' => 'NOT',
		'$between' => 'BETWEEN',
		'$in' => 'IN',
	);

	protected $clause = array(
		'#' => array('', 'buildClause', 2),
		'select' => array('SELECT', 'buildClause', 1),
		'insert' => array('INSERT', 'buildClause', 1),
		'into' => array('INTO', 'buildClause', 4),
		'update' => array('UPDATE', 'buildClause', 1),
		'delete' => array('DELETE', 'buildClause', 1),
		'from' => array('FROM', 'buildClause', 1),
		'values' => array('VALUES', 'buildClause', 1),
		'set' => array('SET', 'buildClause', 3),
		'join' => array('LEFT JOIN', 'buildJoin', 2),
		'innerjoin' => array('INNER JOIN', 'buildJoin', 2),
		'rawjoin' => array('', 'buildJoin', 2),
		'where' => array('WHERE', 'buildWhere', 1),
		'groupby' => array('GROUP BY', 'buildClause', 1),
		'having' => array('HAVING', 'buildWhere', 1),
		'orderby' => array('ORDER BY', 'buildClause', 1),
		'limit' => array('LIMIT', 'buildClause', 1),
		'union' => array('UNION', 'buildClause', 2),
		'%' => array('', 'buildClause', 2),
	);

	function namedScopes($value = null)
	{
		if (is_null($value)) return $this->namedScopes;
		$this->namedScopes = $value;
		return $this;
	}

	function defaultTable($value = null)
	{
		if (is_null($value)) return $this->defaultTable;
		$this->defaultTable = $value;
		return $this;
	}

	function params($args, $query = null, $boundParams = array())
	{
		$query = is_null($query) ? array() : $query ;
		if (!isset($query['params'])) {
			$arg = array_shift($args);
			if (!$arg) return $boundParams;
			return array_merge($boundParams, $arg);
		}
		$params = $query['params'];
		$params = is_array($params) ? implode(',', $params) : $params ;
		$params = is_string($params) ? array_map('trim', explode(',', $params)) : $params ;
		$params = array_unique($params);
		$unbound = array_diff($params, array_keys($boundParams));
		$combined = array();
		$i = 0;
		foreach ($unbound as $key) {
			if (array_key_exists($i, $args)) {
				$combined[$key] = $args[$i];
			}
			$i++;
		}
		return array_merge($boundParams, $combined);
	}

	function build($query = array(), $namedScopes = null)
	{
		if (!is_null($namedScopes)) $this->namedScopes($namedScopes);
		$exps = array();
		foreach ($this->clause as $key => $value) {
			list($phrase, $build, $type) = $value;
			if (isset($query[$key]) && !empty($query[$key])) {
				if ($phrase !== 'UNION' || isset($query['select'])) $exps[] = $phrase;
				if ($type === 1) {
					$exps[] = $this->{$build}($query[$key]);
				} else if ($type === 2) {
					$exps[] = $this->{$build}($query[$key], ' ' . $phrase . ' ');
				} else if ($type === 3) {
					$exps[] = $this->{$build}($query[$key], ', ', ' = ', false);
				} else if ($type === 4) {
					$exps[] = $this->{$build}($query[$key], ' ');
				}
			}
		}
		return trim(implode(' ', $exps));
	}

	function buildSelectQuery($query = array(), $namedScopes = null)
	{
		if (!is_null($namedScopes)) $this->namedScopes($namedScopes);
		if (!isset($query['select']) && !isset($query['union'])) {
			$query['select'] = '*';
		}
		if (!isset($query['from']) && $this->defaultTable() != '') {
			$query['from'] = $this->defaultTable();
		}
		return $this->build($query);
	}

	function newBuilder()
	{
		return Gongo_Locator::get($this->defaultBuilder)->namedScopes($this->namedScopes());
	}

	function subQuery($scopes)
	{
		$q = $this->newBuilder();
		foreach ($scopes as $key => $scope) {
			if (!is_string($key) && is_string($scope)) $q->{$scope};
		}
		return $q->getQuery();
	}

	function buildSubQuery($query = array())
	{
		if (is_array($query)) {
			if (isset($query[0]) && is_string($query[0])) {
				return $this->buildSelectQuery($this->subQuery($query));
			} else {
				return $this->buildSelectQuery($query);
			}
		} else if ($query instanceof Gongo_Db_GoQL) {
			return $this->buildSelectQuery($query->getQuery());
		}
	}

	function buildClause($phrase, $delim = ', ', $conj = ' AS ', $after = true)
	{
		$phrase = is_string($phrase) ? array_map('trim', explode(',', $phrase)) : $phrase ;
		$newPhrase = array();
		foreach ($phrase as $k => $v) {
			$p = $v;
			$a = $after ? (!is_string($k) ? '' : $conj . $k) : '' ;
			$b = $after ? '' : (!is_string($k) ? '' : $k . $conj) ;
			if (is_array($v)||is_object($v)) {
				$p = '(' . $this->buildSubQuery($v) . ')';
			}
			$newPhrase[] = $b . $p . $a;
		}
		return implode($delim, $newPhrase) ;
	}

	function buildJoin($phrase, $delim = '  ')
	{
		$phrase = is_string($phrase) ? array_map('trim', explode(',', $phrase)) : $phrase ;
		$newPhrase = array();
		foreach ($phrase as $k => $v) {
			$p = '';
			if (is_int($k)) {
				if (is_string($v)) $p = $v;
			} else {
				if (stripos($k, ' AS ') !== false) {
					$p = $k . ' ON ' . $this->buildWhere($v);
				} else if (is_array($v)) {
					if ($delim === '  ') {
						list($t, $j, $o) = $v;
					} else {
						list($j, $o) = $v;
					}
					if (is_string($j)) {
						$join = $j;
					} else if (is_array($j)) {
						$join = '('. $this->buildSubQuery($j) . ')';
					}
					if (is_string($o)) {
						$on = $o;
					} else if (is_array($o)) {
						$on = $this->buildWhere($o);
					}
					$p = ($delim !== '  ' ? '' : $t . ' ') . $join . ' AS ' . $k . ' ON ' . $on ;
				}
			}
			$newPhrase[] = $p;
		}
		return implode($delim === '  ' ? ' ' : $delim, $newPhrase) ;
	}

	function buildWhere($cond, $mode = 'AND')
	{
		if ($mode === 'NOT') {
			return $mode . ' ' . $this->buildWhere($cond, 'AND');
		} else if ($mode === 'BETWEEN') {
			$col = !is_array($cond[0]) ? $cond[0] : '(' . $this->buildSubQuery($cond[0]) . ')' ;
			$min = !is_array($cond[1]) ? $cond[1] : '(' . $this->buildSubQuery($cond[1]) . ')' ;
			$max = !is_array($cond[2]) ? $cond[2] : '(' . $this->buildSubQuery($cond[2]) . ')' ;
			return $col . ' BETWEEN ' . $min . ' AND ' . $max ;
		} else if ($mode === 'IN') {
			$col = !is_array($cond[0]) ? $cond[0] : '(' . $this->buildSubQuery($cond[0]) . ')' ;
			$set = !is_array($cond[1]) ? $cond[1] : '(' . $this->buildSubQuery($cond[1]) . ')' ;
			return $col . ' IN ' . $set;
		}
		if (!is_array($cond)) return $cond ;
		if (strpos($mode, '#') === 0) {
			return substr($mode, 1) . ' (' . $this->buildSubQuery($cond) . ')';
		}
		$exps = array();
		foreach ($cond as $k => $v) {
			$m = isset($this->operator[$k]) ? $this->operator[$k] : (strpos($k,'#') === 0 ? $k : 'AND') ;
			$exps[] = $this->buildWhere($v, $m);
		}
		$exp = implode(" {$mode} ", $exps);
		return count($exps) > 1 ? '(' . $exp . ')' : $exp ;
	}
}
