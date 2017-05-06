<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Mysql数据库扩展入口类OcaraMysqli
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Database;
use Ocara\DatabaseBase;
use Ocara\Interfaces\Database as DatabaseInterface;
use Ocara\Interfaces\Sql as SqlInterface;

class MysqliDatabase extends DatabaseBase implements DatabaseInterface, SqlInterface
{
	/**
	 * @var $_pdoName pdo扩展名
	 * @var $_defaultPort 默认数据库端口
	 */
	protected $_pdoName = 'pdo_mysql';
	protected $_defaultPort = '3306';

	/**
	 * SQL过滤关键字
	 * @var array
	 */
	protected $_keywords = array(
		'select', 'insert', 'update', 'replace',
		'join', 'delete', 'union', 'load_file',
		'outfile'
	);

	/**
	 * 不加引号的字段类型
	 * @var array
	 */
	protected static $_quoteBackList = array(
		'tinyint', 	'smallint',  'int',
		'decimal',  'timestamp', 'float',
		'bigint', 	'mediumint', 'double',
		'integer', 	'year',		 'bit',
		'bool', 	'boolean',
	);

	/**
	 * @param array $config
	 * @return array
	 */
	public function getPdoParams($config)
	{
		$config['port'] = $config['port'] ? $config['port'] : $this->_defaultPort;
		$params = array(
			'dsn'      => "mysql:dbname={$config['name']};host={$config['host']};port={$config['port']}",
			'username' => $config['username'],
			'password' => $config['password'],
			'options'  => $config['options'],
			'name'     => $config['name']
		);

		return $params;
	}

	/**
	 * @param string $table
	 * @return array
	 */
	public function getFields($table)
	{
		$table  = $this->getTableName($table);
		$sql    = $this->getShowFieldsSql($table);
		$data   = $this->query($sql);
		$fields = array();

		foreach ($data as $row) {
			$fieldRow = array();
			$fieldRow['name'] = $row['Field'];
			$fieldRow['desc'] = $row['Comment'];
			$pos = strpos($row['Type'], '(', 0);
			$fieldRow['type'] = strtolower(substr($row['Type'], 0, $pos));
			$fields[$row['Field']] = $fieldRow;
		}

		return $fields;
	}

	/**
	 * @param $charset
	 * @return array|bool|object|void
	 */
	public function setCharset($charset)
	{
		return $this->query('SET NAMES ' . $charset);
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	public function selectDb($name)
	{
		if ($this->_isPdo) {
			$sql = $this->getSelectDbSql($name);
			return $this->_plugin->query($sql);
		}

		return $this->_plugin->select_db($name);
	}

	/**
	 * 切换数据库的SQL
	 * @param string $name
	 */
	public function getSelectDbSql($name)
	{
		return "USE " . $name;
	}

	/**
	 * 执行事务
	 * @param string $type
	 * @param string $param
	 * @param boolean $debug
	 */
	public function trans($type, $value = null, $debug = false)
	{
		$sql = $this->getTransSql($type, $value);
		$result = $sql ? $this->query($sql, $debug) : false;
		return $result;
	}

	/**
	 * @param $fields
	 * @param array $data
	 * @return array
	 */
	public function formatFieldValues($fields, $data = array())
	{
		foreach ($data as $key => $value) {
			if (in_array($fields[$key]['type'], self::$_quoteBackList)) {
				$type = $fields[$key]['type'];
				if ($type == 'float') {
					$data[$key] = (float)$value;
				} elseif ($type == 'double') {
					$data[$key] = (double)$value;
				} elseif (strstr($type, 'bool')) {
					$data[$key] = (bool)$value;
				} else {
					$data[$key] = (int)$value;
				}
			} else {
				$data[$key] = (string)$value;
			}
		}
		return $data;
	}
}