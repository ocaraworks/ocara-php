<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Mysql数据库扩展入口类MysqliDatabase
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Databases;

use Ocara\Core\DatabaseBase;
use Ocara\Interfaces\Database as DatabaseInterface;
use Ocara\Interfaces\Sql as SqlInterface;
use Ocara\Exceptions\Exception;

class MysqliDatabase extends DatabaseBase implements DatabaseInterface, SqlInterface
{
	/**
	 * @var $pdoName pdo扩展名
	 * @var $defaultPort 默认数据库端口
	 */
	protected $pdoName = 'pdo_mysql';
	protected $defaultPort = '3306';
	protected $defaultFields = '*';

	/**
	 * SQL过滤关键字
	 * @var array
	 */
	protected $keywords = array(
		'select', 'insert', 'update', 'replace',
		'join', 'delete', 'union', 'load_file',
		'outfile'
	);

	/**
	 * 不加引号的字段类型
	 * @var array
	 */
	protected static $quoteBackList = array(
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
		$config['port'] = $config['port'] ? : $this->defaultPort;
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
     * 获取字段
     * @param string $table
     * @return array
     * @throws Exception
     */
	public function getFields($table)
	{
		$table     = $this->getTableFullname($table);
		$sqlData   = $this->getShowFieldsSql($table);
		$data      = $this->query($sqlData);
		$fields    = array('list' => array(), 'autoIncrementField' => OC_EMPTY);
		$isComment = ocConfig('USE_FIELD_DESC_LANG', false);

		foreach ($data as $row) {
			$fieldRow = array();
			$fieldRow['name'] = $row['Field'];
			if (strstr($row['Type'], '(')) {
				$pos = strpos($row['Type'], '(', 0);
				$endPos = strpos($row['Type'], ')', 0);
				$fieldRow['type'] = strtolower(substr($row['Type'], 0, $pos));
				$fieldRow['length'] = (integer)substr($row['Type'], $pos + 1, $endPos - $pos - 1);
			} else {
				$fieldRow['type'] = $row['Type'];
				$fieldRow['length'] = 0;
			}
			if ($isComment && $row['Comment']) {
				$fieldRow['lang'] = $row['Comment'];
			} else {
				$fieldRow['lang'] = ocHump($fieldRow['name'], OC_SPACE);
			}
			$fieldRow['isNull'] = $row['Null'] == 'NO' ? OC_FALSE : OC_TRUE;
			$fieldRow['isPrimary'] = $row['Key'] == 'PRI' ? OC_TRUE : OC_FALSE;
			$fieldRow['defaultValue'] = (string)$row['Default'];
            $fieldRow['Extra'] = $row['Extra'];
			$fields['list'][$row['Field']] = $fieldRow;
		}

		return $fields;
	}

    /**
     * 设置字符集
     * @param $charset
     * @return array|bool|mixed
     * @throws Exception
     */
	public function setCharset($charset)
	{
		$sqlData = $this->getSetCharsetSql($charset);
		return $this->query($sqlData);
	}

    /**
     * 选择数据库
     * @param string $name
     * @return array|bool|mixed
     * @throws Exception
     */
	public function selectDatabase($name)
	{
		if ($this->isPdo()) {
			$sqlData = $this->getSelectDbSql($name);
			return $this->query($sqlData);
		}

		return $this->plugin->select_db($name);
	}

	/**
	 * @param $fields
	 * @param array $data
	 * @return array
	 */
	public function formatFieldValues($fields, $data = array())
	{
		foreach ($data as $key => $value) {
			$type = 'string';
			if (isset($fields[$key]) && in_array($fields[$key]['type'], self::$quoteBackList)) {
				$type = $fields[$key]['type'];
				if ($type == 'float') {
					$type = 'float';
				} elseif ($type == 'double') {
					$type = 'double';
				} elseif (strstr($type, 'bool')) {
					$type = 'boolean';
				} else {
					$type = 'integer';
				}
			}
			if (!self::checkOcaraSqlTag($value)) {
				if (is_array($value)) {
					foreach ($value as $k => $v) {
						settype($value[$k], $type);
					}
				} elseif (ocScalar($value)) {
					settype($value, $type);
				}
			}
			$data[$key] = $value;
		}
		return $data;
	}
}
