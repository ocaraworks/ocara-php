<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   SQL语句生成类Sql
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

class Sql extends Base
{
	/**
	 * 给逗号分隔的列表加引号
	 * @param array|string $list
	 * @param string $quote
	 * @return array|bool|mixed|string
	 */
	public function quoteList($list, $quote = OC_QUOTE)
	{
		$sql = $list;

		if ($list && is_array($list)) {
			$sql  = implode(',', $list);
			if (is_numeric(reset($list))) {
				$sql = $this->parseValue($sql, 'where', false, false);
			} else {
				$sql = $this->parseValue($sql, 'where', true, false);
			}
		}

		return $sql;
	}

	/**
	 * 转义字符
	 * @param string $name
	 * @param bool $addSlashes
	 * @return array|bool|mixed|string
	 */
	public function filterName($name, $addSlashes = true)
	{
		if ($addSlashes) {
			$str = $this->_plugin->real_escape_string($name);
			if ($str) {
				return $this->filterSql($str, false, true, true);
			}
		}

		return $this->filterSql($name, $addSlashes, true, true);
	}

	/***
	 * SQL安全过滤
	 * @param string $content
	 * @param bool $addSlashes
	 * @return array|bool|mixed|string
	 */
	public function filterValue($content, $addSlashes = true)
	{
		if ($mt = self::checkOcaraSqlTag($content)) {
			return $mt[1];
		} else {
			if ($addSlashes) {
				$str = $this->_plugin->real_escape_string($content);
				if ($str) {
					return $this->filterSql($str, false);
				}
			}
			return $this->filterSql($content, $addSlashes);
		}
	}

	/**
	 * 值格式解析
	 * @param string $value
	 * @param string $paramType
	 * @param bool $ifQuote
	 * @param bool $prepare
	 * @return array|bool|mixed|string
	 * @throws Exception
	 */
	public function parseValue($value, $paramType = 'where', $ifQuote = true, $prepare = true)
	{
		if (ocScalar($value)) {
			if ($mt = self::checkOcaraSqlTag($value)) {
				return $mt[1];
			} else {
				if ($this->_prepared && $prepare) {
					$value = $this->filterSql($value, false);
					$this->_params[$paramType][] = $value;
					return '?';
				}
				return $this->getValueSql($value, $ifQuote);
			}
		}

		Error::show('need_string_field');
	}

	/**
	 * [别名.]字段解析
	 * @param string $field
	 * @param bool $alias
	 * @return bool|string
	 * @throws Exception
	 */
	public function parseField($field, $alias = false)
	{
		if (!is_string($field)) {
			Error::show('invalid_field_name');
		}

		if ($mt = self::checkOcaraSqlTag($field)) {
			return $mt[1];
		}

		$field = $this->filterName($field);

		if (empty($field)) return false;

		return $this->getFieldNameSql($field, $alias);
	}

	/**
	 * SQL过滤
	 * @param string $content
	 * @param bool $addSlashes
	 * @param bool $equal
	 * @return array|bool|mixed|string
	 */
	public function filterSql($content, $addSlashes = true, $equal = false)
	{
		return Filter::sql($content, $addSlashes, $this->_config['keywords'], $equal);
	}

	/**
	 * 字段转换
	 * @param string $sql
	 * @param array $mapFields
	 * @param string $currentAlias
	 * @param bool $field2Alias
	 * @return bool|string
	 */
	public function transformFields($sql, $mapFields, $currentAlias, $field2Alias = false)
	{
		$exp = '/([^\w\.]+)+((\w+)\.)?(%s)([^\w\.]+)+/i';
		$newSql = chr(32) . $sql . chr(32);

		foreach ($mapFields as $alias => $row) {
			if ($field2Alias) {
				$row = array_flip($row);
			}
			foreach ($row as $search => $replace) {
				if (preg_match(sprintf($exp, $search), $newSql, $mt)) {
					if (!$mt[3]) {
						$mt[3] = $currentAlias;
					}
					if ($alias == $mt[3]) {
						$newSql = $this->getFieldAliasSql($sql, $replace);
					}
					return trim($newSql);
				}
			}
		}

		return $sql;
	}

	/**
	 * 获取字段别名SQL
	 * @param string $field
	 * @param string $alias
	 * @return string
	 */
	public function getFieldAliasSql($field, $alias)
	{
		return $field ? $field . ' AS ' . $alias : OC_EMPTY;
	}

	/**
	 * 检查Ocara代码标记
	 * @param string $value
	 * @return array
	 */
	public static function checkOcaraSqlTag($value)
	{
		if (preg_match('/^' . OC_SQL_TAG . '(.*)$/i', $value, $mt)) {
			return $mt;
		}
		return array();
	}

	/**
	 * 检查是否是字符串或数字条件
	 * @param mixed $condition
	 * @return bool
	 * @throws Exception
	 */
	public function checkStringCondition($condition)
	{
		if (ocScalar($condition)) {
			return true;
		}
		Error::show('need_string_condition');
	}

	/**
	 * SELECT语句
	 * @param $fields
	 * @param $tables
	 * @param $options
	 * @return string
	 */
	public function getSelectSql($fields, $tables, $options)
	{
		return "SELECT {$fields} FROM {$tables} "
			. $this->getOptionSql('WHERE', $options)
			. $this->getOptionSql('GROUP', $options)
			. $this->getOptionSql('HAVING', $options)
			. $this->getOptionSql('ORDER', $options)
			. $this->getOptionSql('LIMIT', $options)
			. $this->getOptionSql('MORE', $options);
	}

	/**
	 * INSERT语句
	 * @param $table
	 * @param $data
	 * @return string
	 */
	public function getInsertSql($table, $data)
	{
		return $this->getInsertSqlBase('INSERT', $table, $data);
	}

	/**
	 * INSERT基本语句
	 * @param $type
	 * @param $table
	 * @param $data
	 * @return string
	 */
	public function getInsertSqlBase($type, $table, $data)
	{
		$table = $this->filterName($table);
		$fields = $values = array();

		foreach ($data as $key => $value) {
			$fields[] = $this->parseField($key);
			$values[] = $this->parseValue($value, 'set');
		}

		$fields = implode(',', $fields);
		$values = implode(',', $values);

		return $type . " INTO {$table}({$fields}) VALUES({$values})";
	}

	/**
	 * UPDATE语句
	 * @param $table
	 * @param $data
	 * @param $where
	 * @return string
	 */
	public function getUpdateSql($table, $data, $where)
	{
		$this->checkStringCondition($where);

		$set   = null;
		$table = $this->filterName($table);

		if (is_array($data)) {
			$array = array();
			foreach ($data as $key => $value) {
				$key = $this->parseField($key);
				$value = $this->parseValue($value, 'set');
				$array[] = "{$key} = {$value}";
			}
			$set = implode(',', $array);
		}

		return "UPDATE {$table} SET {$set} " . ($where ? " WHERE {$where} " : OC_EMPTY);
	}

	/**
	 * REPLACE语句
	 * @param $table
	 * @param $data
	 * @return string
	 */
	public function getReplaceSql($table, $data)
	{
		return $this->getInsertSqlBase('REPLACE', $table, $data);
	}

	/**
	 * DELETE语句
	 * @param $table
	 * @param $where
	 * @param bool $delete
	 * @return string
	 */
	public function getDeleteSql($table, $where, $delete = false)
	{
		$this->checkStringCondition($where);

		$table = $this->filterName($table);
		$delete = $this->filterName($delete);

		return "DELETE {$delete} FROM {$table}" . ($where ? " WHERE {$where} " : OC_EMPTY);
	}

	/**
	 * 获取表的字段信息
	 * @param $table
	 * @return string
	 */
	public function getShowFieldsSql($table)
	{
		$table = $this->filterName($table);
		return "SHOW FULL FIELDS FROM {$table}";
	}

	/**
	 * 获取联接语句
	 * @param $type
	 * @param $table
	 * @param $alias
	 * @param $on
	 * @return string
	 */
	public function getJoinSql($type, $table, $alias, $on)
	{
		$type  = strtoupper($type ? $type . ' JOIN ' : false);
		$alias = $this->filterName($alias);
		$on    = $this->parseCondition($on);
		$data  = $this->wrapSign(array($type, $alias, $on));
		$sql   = $data[0] . $table;

		if ($data[1]) {
			$sql = $sql . ' AS ' . $data[1];
		}

		if ($data[2]) {
			$sql = $sql . ' ON ' . $data[2];
		}

		return $sql;
	}

	/**
	 * 获取最后一次插入的ID
	 * @return string
	 */
	public function getLastIdSql()
	{
		return "SELECT last_insert_id() AS id";
	}

	/**
	 * 获取limit字符串
	 * @param $limit
	 * @return array|bool|mixed|string
	 */
	public function getLimitSql($limit)
	{
		if (is_array($limit) && count($limit) >= 2) {
			$limit = "{$limit[0]}, {$limit[1]}";
		}

		$str = $this->_plugin->real_escape_string($limit);
		if ($str) {
			return $this->filterSql($str, false, true);
		}

		return $this->filterSql($limit, true, true);
	}

	/**
	 * 获取In语句
	 * @param $field
	 * @param $list
	 * @param bool $alias
	 * @param string $sign
	 * @return string
	 */
	public function getInSql($field, $list, $alias = false, $sign = 'IN')
	{
		$sign = $sign ? $sign : 'IN';

		if (is_array($list)) {
			$list = $this->quoteList($list);
		}

		$field = $this->parseField($field, $alias);
		if (ocScalar($list) && $field) {
			return " {$field} {$sign} ($list)";
		}

		return OC_EMPTY;
	}

	/**
	 * 获取Between语句
	 * @param string $field
	 * @param string $value1
	 * @param mixed $value2
	 * @param string $alias
	 * @return string
	 */
	public function getBetweenSql($field, $value1, $value2, $alias = null)
	{
		$value1 = $this->parseValue($value1, 'where');
		$value2 = $this->parseValue($value2, 'where');

		return $this->parseField($field, $alias) . " BETWEEN {$value1} AND {$value2}";
	}

	/**
	 * 获取统计字段SQL
	 * @param string $countFiled
	 * @param string $fieldName
	 * @param bool $isGroup
	 * @return array|string
	 */
	public function getCountSql($countFiled, $fieldName = 'total', $isGroup = false)
	{
		$fieldName = $this->filterName($fieldName);

		if ($countFiled) {
			$sql = Filter::addSlashes("COUNT({$countFiled}) AS {$fieldName}");
		} else {
			$countFiled = $isGroup ? 1 : 'COUNT(1)';
			$sql = Filter::addSlashes("{$countFiled} AS {$fieldName}");
		}

		return $sql;
	}

	/**
	 * 获取选项语句
	 * @param $type
	 * @param $options
	 * @return array|bool|mixed|string
	 */
	public function getOptionSql($type, $options)
	{
		if (empty($options)) return false;

		$ltype = strtolower($type);

		if (is_string($options) && $type == 'WHERE') {
			return $options ? " WHERE {$options} " : OC_EMPTY;
		}

		if (is_array($options) && array_key_exists($ltype, $options)) {
			$content = $options[$ltype];
			switch ($type) {
				case 'WHERE':
				case 'HAVING':
					$this->checkStringCondition($content);
					break;
				case 'GROUP':
					$content = $this->filterName($content);
					break;
				case 'MORE':
					if ($content) {
						$content = array_map(array($this, 'filterValue'), $content);
						$content = OC_SPACE . implode(OC_SPACE, $content);
					}
					return $content;
			}
			if (in_array($type, array('GROUP', 'ORDER'))) {
				$type = $type . ' BY';
			}
			return $content ? " {$type} {$content} " : OC_EMPTY;
		}

		return false;
	}

	/**
	 * 解析查询条件
	 * @param $condition
	 * @param string $link
	 * @param string $sign
	 * @param bool $alias
	 * @return array|bool|mixed|string
	 */
	public function parseCondition($condition, $link = 'AND', $sign = '=', $alias = false)
	{
		if (ocEmpty($condition)) return false;

		if (is_array($condition) && $condition) {
			$condition = trim($this->getFieldCondition($condition, $link, $sign, $alias));
			return $condition;
		}

		$condition = $this->filterValue($condition);
		$alias = $this->getAliasSql($alias);

		return $this->wrapWhere($alias . (string)$condition);
	}

	/**
	 * 获取别名SQL
	 * @param $alias
	 * @return bool|string
	 */
	public function getAliasSql($alias)
	{
		if ($alias) {
			$alias = $this->filterName($alias);
			return "`{$alias}`";
		}

		return false;
	}

	/**
	 * 组装条件（加上括号）
	 * @param $str
	 * @return string
	 */
	public static function wrapWhere($str)
	{
		return '(' . $str . ')';
	}

	/**
	 * 获取字段列表SQL
	 * @param array $fields
	 * @param array $aliasFields
	 * @param $currentAlias
	 * @param bool $alias
	 * @return string
	 */
	public function getFieldsSql(array $fields, array $aliasFields, $currentAlias, $alias = false)
	{
		foreach ($fields as $key => $value) {
			if (!self::isOptionFieldSql($value)) {
				$value = $this->getFieldNameSql($value,  $alias);
				if (!preg_match('/\sas\s/', $value, $mt)) {
					$value = $this->transformFields($value, $aliasFields, $currentAlias, true);
				}
				$fields[$key] = $value;
			}
		}

		return implode(',', $fields);
	}

	/**
	 * 字段组合
	 * @param array $fields
	 * @param array $aliasFields
	 * @param bool $unJoined
	 * @param $currentAlias
	 * @param array $primaries
	 * @return mixed
	 */
	public function combineFieldsSql(array $fields, array $aliasFields, $unJoined, $currentAlias, array $primaries = array())
	{
		foreach ($fields as $key => $value) {
			if ($option = self::isOptionFieldSql($value)) {
				$fields[$key] = $option;
			} else {
				$fields[$key] = $value . ',';
			}
		}

		$primaryFields = array();
		foreach ($primaries as $primaryField) {
			if (!in_array($primaryField, $fields)) {
				$primaryFields[] = $primaryField;
			}
		}

		$alias = $unJoined ? false : $currentAlias;
		$primaryFields = $this->getFieldsSql($primaryFields, $aliasFields, $currentAlias, $alias);
		if ($primaryFields) {
			$fields[] = $primaryFields;
		}

		return trim(implode(OC_SPACE, $fields), ',');
	}

	/**
	 * 是否字段选项
	 * @param $value
	 * @return null
	 */
	public static function isOptionFieldSql($value)
	{
		if (preg_match('/^\{(.*)\}$/', $value, $mt)) {
			return $mt[1];
		}

		return null;
	}

	/**
	 * 获取字段名称SQL
	 * @param string $field
	 * @param bool $alias
	 * @return string
	 */
	public function getFieldNameSql($field, $alias = false)
	{
		if ($this->hasAlias($field) || $field == '*') {
			return $field;
		}

		if ($alias) {
			return "{$alias}.{$field}";
		}

		$field = ocIsStandardName($field) ? "`{$field}`" : $field;
		return $field;
	}

	/**
	 * 检测是否含有别名
	 * @param $field
	 * @return bool
	 */
	public function hasAlias($field)
	{
		if (preg_match('/^([`\w]*)\.(([`\s\w]+)|(\*))$/', $field, $mt)) {
			return $mt;
		}

		return false;
	}

	/**
	 * 获取字段值SQL
	 * @param $val
	 * @param bool $ifQuote
	 * @return array|bool|mixed|string
	 */
	public function getValueSql($val, $ifQuote = true)
	{
		if (is_numeric($val)) {
			return $val;
		}

		if ($val === null) {
			return 'NULL';
		}

		$str = $this->_plugin->real_escape_string($val);
		if ($str) {
			$val = $this->filterSql($str, false);
		} else {
			$val = $this->filterSql($val);
		}

		$val = $ifQuote ? OC_QUOTE . $val . OC_QUOTE : $val;
		return $val;
	}

	/**
	 * 给符号加空格
	 * @param $sign
	 * @return array|null|string
	 */
	public function wrapSign($sign)
	{
		if (is_array($sign)) {
			foreach ($sign as $key => $value) {
				if ($value) {
					$sign[$key] = OC_SPACE . $value . OC_SPACE;
				} else {
					$sign[$key] = null;
				}
			}
			return $sign;
		}

		return $sign ? OC_SPACE . $sign . OC_SPACE : null;
	}

	/**
	 * 获取表名
	 * @param $databaseName
	 * @param $tableName
	 * @return string
	 */
	public function getTableNameSql($databaseName, $tableName)
	{
		return $databaseName ? "`{$databaseName}`.`$tableName`" : "`$tableName`";
	}

	/**
	 * 格式化键值数组
	 * @param mixed $data
	 * @param string $link
	 * @param string $sign
	 * @param bool $alias
	 * @return string
	 */
	public function getFieldCondition($data, $link = ',', $sign = '=', $alias = false)
	{
		if (!is_array($data) || empty($data)) {
			return $data;
		}

		$link   = $link ? $link : ',';
		$sign   = $sign ? strtoupper(trim(Filter::replaceSpace($sign))) : '=';
		$result = array();

		if (in_array($sign, array('IN', 'NOT IN'))) {
			foreach ($data as $key => $value) {
				$result[] = $this->getInSql($key, $value, $alias, $sign);
			}
		} else {
			foreach ($data as $key => $value) {
				$field = $this->parseField($key, $alias);
				$value = $this->parseValue($value, 'where');
				$result[] = "({$field} {$sign} {$value})";
			}
		}

		return implode($this->wrapSign($link), $result);
	}

	/**
	 * 生成条件字符串
	 * @param $data
	 * @return bool|string
	 */
	public function getWhereSql($data)
	{
		if (empty($data)) return false;
		if (!is_array($data)) return $data;

		$where = array();
		$length = count($data);

		for ($i = 0; $i < $length; $i++) {
			$row = $data[$i];
			if (!is_array($row)) {
				$row = array('where' => $row, 'link' => 'AND');
			}
			if (is_array($row['where'])) {
				$row['where'] = implode($this->wrapSign('AND'), $row['where']);
			}
			if ($i == 0) {
				$where[] = $row['where'];
			} else {
				$where[] = $row['link'] . OC_SPACE . $row['where'];
			}
		}

		return implode(OC_SPACE, $where);
	}

	/**
	 * 获取合并查询语句
	 * @param string $sql
	 * @param bool $unionAll
	 * @return string
	 */
	public function getUnionSql($sql, $unionAll = false)
	{
		return ($unionAll ? ' UNION ALL ' : ' UNION ') . $sql;
	}

	/**
	 * 将条件数组连接成条件字符串
	 * @param array $data
	 * @return string
	 */
	public function linkWhere(array $data)
	{
		$sql = OC_EMPTY;

		foreach ($data as $row) {
			list($link, $condition) = $row;
			if (!$sql) {
				$sql = $condition;
			} else {
				$sql .= $this->wrapSign($link ? $link : 'AND') . OC_SPACE . $condition;
			}
		}

		return $sql;
	}
}