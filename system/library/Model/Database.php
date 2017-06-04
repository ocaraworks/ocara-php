<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   数据库模型类Database
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Model;
use Ocara\Request;
use Ocara\Error;
use Ocara\Call;
use Ocara\Cache;
use Ocara\FormToken;
use Ocara\Database as DefaultDatabase;
use Ocara\DatabaseBase;
use Ocara\ModelBase;
use Ocara\Iterator\Database\ObjectRecords;

defined('OC_PATH') or exit('Forbidden!');

abstract class Database extends ModelBase
{
	
	/**
	 * @var @_primary 主键字段列表
	 * @var $_primaries 主键字段数组
	 */
	protected $_driver = null;

	protected $_primary;
	protected $_table;
	protected $_server;
	protected $_name;
	protected $_tag;
	protected $_dataType;
	protected $_fields;

	private $_path;
	private $_master;
	private $_slave;
	private $_database;
	private $_tableName;
	private $_oldTable;
	private $_isOrm;
	private $_selected;

	private $_relations = array();
	private $_changes   = array();
	private $_config    = array();
	private $_sql       = array();
	private $_primaries = array();

	private static $_requirePrimary;

	/**
	 * Model constructor.
	 */
	public function __construct()
	{
		$this->initialize();
	}

	/**
	 * 初始化
	 */
	public function initialize()
	{
		if (self::$_requirePrimary === null) {
			$required = ocConfig('MODEL_REQUIRE_PRIMARY', true);
			self::$_requirePrimary = $required ? true : false;
		}

		if (self::$_requirePrimary && empty($this->_primary)) {
			Error::show('no_primaries');
		}

		$this->_path = ocDir($this->_server, $this->_database);
		$this->_name = self::getClass();
		$this->_tag = $this->_path . $this->_name;
		$this->_tableName = empty($this->_table) ? $this->_name : $this->_table;
		$this->_oldTable = $this->_tableName;

		$this->_join(false, $this->_tableName, 'a');
		$this->setDataType($this->_dataType);
		$this->loadConfig();

		if ($this->_primary) {
			$this->_primaries = explode(',', $this->_primary);
		}

		if (method_exists($this, '_model')) $this->_model();
		return $this;
	}

	/**
	 * 获取Model标记
	 * @return string
	 */
	public function getTag()
	{
		return $this->_tag;
	}

	/**
	 * 执行分库分表
	 * @param array $data
	 */
	public function sharding(array $data = array())
	{
		if (method_exists($this, '_sharding')) {
			$this->_sharding($data);
		}

		return $this;
	}

	/**
	 * 合并查询（去除重复值）
	 * @param object $model
	 * @return $this
	 */
	public function union(\Ocara\Model $model)
	{
		$this->connect()->union($model, false);
		return $this;
	}

	/**
	 * 合并查询
	 * @param object $model
	 * @return $this
	 */
	public function unionAll(\Ocara\Model $model)
	{
		$this->connect()->union($model, true);
		return $this;
	}

	/**
	 * 分库分表 - 修改表名后初始化设置
	 */
	private function _tableInit()
	{
		$tables   = $this->_sql['tables'];
		$oldTable = ocDel($tables, $this->_oldTable);
		$this->_sql['tables'] = array();
		$this->_join(false, $this->_tableName, 'a');

		$newTables = $this->_sql['tables'];
		$newTables[$this->_tableName] = array_merge(
			$oldTable,
			$newTables[$this->_tableName]
		);
		$newTables = array_merge(
			$newTables,
			$tables
		);

		$this->_sql['tables'] = $newTables;
		$this->_oldTable = $this->_tableName;
	}

	/**
	 * 加载配置文件
	 */
	public function loadConfig()
	{
		$this->_config['JOIN'] = array();
		$this->_config['MAP']  = array();
		$this->_config['VALIDATE']  = array();
		$this->_config['LANG']  = array();

		$filePath = $this->getConfigPath();
		$path = ocPath('conf', "model/{$filePath}");

		if (ocFileExists($path)) {
			include ($path);
			if (isset($CONF) && is_array($CONF)) {
				$this->_config = array_merge(
					array_diff_key($this->_config, $CONF),
					array_intersect_key($CONF, $this->_config)
				);
			}
		}

		ksort($this->_config);
	}

	/**
	 * 获取配置数据
	 * @return array
	 * @param string $key
	 * @param string $field
	 */
	public function getConfig($key = null, $field = null)
	{
		if ($num = func_num_args()) {
			if ($key == 'LANG' && empty($this->_config['LANG'])) {
				$filePath = $this->getConfigPath();
				$path = ocPath('lang', 'model' . OC_DIR_SEP . $filePath);
				if (ocFileExists($path)) {
					include ($path);
					if (isset($LANG) && is_array($LANG)) {
						$this->_config['LANG'] = $LANG;
					}
				}
			}
			$config = ocGet($key, $this->_config);
			return isset($config[$field]) ? $config[$field] : $config;
		}

		return $this->_config;
	}

	/**
	 * 修改字段配置
	 * @param $key
	 * @param $field
	 * @param $value
	 */
	public function setConfig($key, $field, $value)
	{
		$config = $this->getConfig($key);
		$config[$key][$field] = $value;
		$this->_config[$key] = $config;
	}

	/**
	 * 获取配置文件路径
	 */
	public function getConfigPath()
	{
		if ($this->_path) {
			$filePath = lcfirst($this->_path) . lcfirst($this->_name);
		} else {
			$filePath = $this->_name;
		}

		return $filePath . '.php';
	}

	/**
	 * 获取当前数据库对象
	 * @param bool $slave
	 */
	public function db($slave = false)
	{
		$name = $slave ? '_slave' : '_driver';
		if (is_object($this->$name)) {
			return $this->$name;
		}

		Error::show('null_database');
	}

	/**
	 * 切换数据库
	 * @param string $name
	 */
	public function selectDatabase($name)
	{
		$this->_database = $name;
	}

	/**
	 * 切换数据表
	 * @param $name
	 */
	public function selectTable($name, $primary = null)
	{
		$this->_table = $name;
		$this->_tableName = $name;

		if ($primary) {
			$this->_primary = $primary;
			if ($this->_primary) {
				$this->_primaries = explode(',', $this->_primary);
			}
		}

		$this->_tableInit();
	}

	/**
	 * 新建ORM模型
	 * @param array $data
	 */
	public function data(array $data = array())
	{
		$data = $this->_getPostData($data);

		if ($data) {
			ocDel($data, FormToken::getTokenTag());
			$this->setProperty($data);
		}

		$this->_isOrm = true;
		return $this;
	}

	/**
	 * 加载数据表的字段以便过滤
	 */
	public function loadFields()
	{
		$this->_fields = $this->connect()->getFields($this->_tableName);
		return $this;
	}

	/**
	 * 获取字段
	 */
	public function getFields()
	{
		if (empty($this->_fields)) {
			$this->loadFields();
		}

		return $this->_fields;
	}

	/**
	 * 获取ORM数据
	 */
	public function getData()
	{
		return $this->getProperty();
	}

	/**
	 * 字段别名映射
	 * @param array $data
	 */
	public function map(array $data)
	{
		$result = array();

		foreach ($data as $key => $value) {
			$key = strtr($key, $this->_config['MAP']);
			if ($this->_fields && !isset($this->_fields[$key])
				|| $key == FormToken::getTokenTag()
			) {
				continue;
			}
			$result[$key] = $value;
		}

		if ($this->_fields) {
			if (!is_object($this->_driver)) {
				$this->_driver = $this->connect();
			}
			$result = $this->_driver->formatFieldValues($this->_fields, $result);
		}

		return $result;
	}

	/**
	 * 设置结果集返回数据类型
	 * @param string $dataType
	 */
	public function setDataType($dataType)
	{
		$this->_dataType = strtolower($dataType);
		return $this;
	}

	/**
	 * 清理SQL
	 */
	public function clearSql()
	{
		$this->_sql = array();
		$this->_join(false, $this->_tableName, 'a');
		$this->_driver = $this->_master;
		return $this;
	}

	/**
	 * 清理ORM数据
	 */
	public function clearData()
	{
		$this->_isOrm = false;
		$this->clearProperty();
		return $this;
	}

	/**
	 * 清理Model的SQL和ORM数据
	 */
	public function clear()
	{
		$this->clearSql();
		$this->clearData();
		return $this;
	}

	/**
	 * 缓存查询的数据
	 * @param string $server
	 * @param bool $required
	 */
	public function cache($server = null, $required = false)
	{
		$server = $server ? $server : 'default';
		$this->_sql['cache'] = array($server, $required);
		return $this;
	}

	/**
	 * 规定使用主库查询
	 */
	public function master()
	{
		$this->_sql['option']['master'] = true;
		return $this;
	}

	/**
	 * 是否是ORM模型
	 */
	public function isOrm()
	{
		return $this->_isOrm;
	}

	/**
	 * 保存记录
	 * @param array $data
	 * @param string|array $condition
	 * @param bool $debug
	 */
	private function _save($data, $condition, $debug = false)
	{
		if ($condition) {
			call_user_func_array('ocDel', array(&$data, $this->_primaries));
			if (method_exists($this, '_beforeUpdate')) {
				$this->_beforeUpdate();
			}
		} else {
			if (method_exists($this, '_beforeCreate')) {
				$this->_beforeCreate();
			}
		}

		$data = $this->map(array_merge($data, $this->getProperty()));
		if (empty($data)) {
			Error::show('fault_save_data');
		}

		if ($this->_changes) {
			$this->db()->transBegin();
		}

		if ($condition) {
			call_user_func_array('ocDel', array(&$data, $this->_primaries));
			$ret = $this->_driver->update($this->_tableName, $data, $condition, $debug);
			if (!$debug){
				$this->_relateSave();
				if(method_exists($this, '_afterUpdate')) {
					$this->_afterUpdate();
				}
			}
		} else {
			$ret = $this->_driver->insert($this->_tableName, $data, $debug);
			if (!$debug) {
				$this->select($this->_driver->getInsertId());
				$this->_relateSave();
				if (method_exists($this, '_afterCreate')) {
					$this->_afterCreate();
				}
			}
		}

		if ($debug === DatabaseBase::DEBUG_RETURN) return $ret;

		$this->clearProperty();
		return $ret;
	}

	/**
	 * 获取最后插入的记录ID
	 * @return mixed
	 */
	public function getInsertId()
	{
		return $this->_driver->getInsertId();
	}

	/**
	 * 预处理
	 * @param bool $prepare
	 */
	public function prepare($prepare = true)
	{
		$this->_driver->is_prepare($prepare);
	}

	/**
	 * 绑定参数
	 * @param string $type
	 * @param mixed $args
	 */
	public function bind($type, &$args)
	{
		call_user_func_array(array($this->_driver, 'bind'), func_get_args());
	}

	/**
	 * 保存数据（ORM模型）
	 * @param $debug
	 * @return mixed
	 */
	public function save($debug = false)
	{
		$condition = $this->_getCondition();
		$data = array();

		if ($condition) {
			$result = $this->_update('update', $debug, $condition, $data);
		} else {
			$result = $this->_save($data, false, $debug);
		}

		return $result;
	}

	/**
	 * 新建记录
	 * @param array $data
	 * @param bool $debug
	 */
	public function create(array $data = array(), $debug = false)
	{
		$this->connect();
		$data = $this->map($this->_getPostData($data));
		$result = $this->_save($data, false, $debug);
		return $result;
	}

	/**
	 * 获取数据
	 * @param $data
	 * @return array|null|string
	 */
	protected function _getPostData($data)
	{
		if (empty($data)) {
			$data = Request::getPost();
			if ($data) {
				$this->loadFields();
			}
		}

		return $data;
	}

	/**
	 * 更新记录
	 * @param string|array $condition
	 * @param bool $debug
	 */
	public function update(array $data, $debug = false)
	{
		$condition = $this->_getCondition();
		$result = $this->_update('update', $debug, $condition, $data);
		return $result;
	}

	/**
	 * 删除记录
	 * @param string|array $condition
	 * @param bool $debug
	 */
	public function delete($debug = false)
	{
		$condition = $this->_getCondition();
		$result = $this->_update('delete', $debug, $condition);
		return $result;
	}

	/**
	 * 获取操作条件
	 * @return array|null
	 */
	private function _getCondition()
	{
		$this->connect();
		$condition = $this->_genSql(false);

		return $condition;
	}

	/**
	 * 获取数据更新或删除的条件
	 * @param string $type
	 * @param bool $debug
	 * @param array $data
	 */
	private function _update($type, $debug, $condition, array $data = array())
	{
		if (empty($condition)) Error::show('need_condition');

		if ($type == 'update') {
			$result = $this->_save($data, $condition, $debug);
		} else {
			if (!$debug && method_exists($this, '_beforeDelete')) {
				$this->_beforeDelete();
			}
			$result = $this->_driver->delete($this->_tableName, $condition, $debug);
			if (!$debug && !$this->_driver->errorExists() && method_exists($this, '_afterDelete')) {
				$this->_afterDelete();
			}
		}

		if ($debug === DatabaseBase::DEBUG_RETURN) return $result;

		return $result;
	}

	/**
	 * 用SQL语句获取多条记录
	 * @param string $sql
	 * @param bool $debug
	 */
	public function query($sql, $debug = false)
	{
		return $sql ? $this->connect(false)->query($sql, $debug) : false;
	}

	/**
	 * 用SQL语句获取一条记录
	 * @param string $sql
	 * @param bool $debug
	 */
	public function queryRow($sql, $debug = false)
	{
		return $sql ? $this->connect(false)->queryRow($sql, $debug) : false;
	}

	/**
	 * 获取SQL
	 * @return array
	 */
	public function getSql()
	{
		return $this->_sql;
	}

	/**
	 * 设置SQL
	 * @param $sql
	 */
	public function setSql($sql)
	{
		$this->_sql = $sql;
	}

	/**
	 * 按主键选择一行记录，并保存为属性
	 * @param string|numric|array $condition
	 * @param string|array $option
	 * @param bool $debug
	 */
	public function selectOne($condition = false, $option = null, $debug = false)
	{
		$this->_selected = true;
		$data = $this->findFirst($condition, $option, $debug);

		if ($debug === DatabaseBase::DEBUG_RETURN) return $data;
		if ($data) {
			$this->data($data);
			return $this;
		}

		return null;
	}

	/**
	 * 按主键选择一行记录
	 * @param string|numric|array $primaryValues
	 * @param string|array $option
	 * @param bool $debug
	 */
	public static function select($primaryValues, $option = null, $debug = false)
	{
		$model = new static();
		$condition = $model->_getPrimaryCondition($primaryValues);
		$model->selectOne($condition, $option, $debug);

		return $model;
	}

	/**
	 * 选择多条记录
	 * @param $condition
	 * @param null $options
	 * @param bool $debug
	 * @return array|ObjectRecords
	 */
	public static function selectAll($condition, $options = null, $debug = false)
	{
		$records = new ObjectRecords(get_called_class(), array($condition), $options, $debug);

		$times = isset($options['times']) ? $options['times'] : 0;
		$start = isset($options['start']) ? $options['start'] : 0;
		$rows = isset($options['rows']) ? $options['rows'] : 1;

		$records->setLimit($times, $start, $rows);

		return $records;
	}

	/**
	 * 获取主键条件
	 * @param $condition
	 * @return array
	 * @throws \Ocara\Exception
	 */
	private function _getPrimaryCondition($condition)
	{
		if (empty($this->_primaries)) {
			Error::show('no_primary');
		}

		if (ocEmpty($condition)) {
			Error::show('need_primary_value');
		}

		if (is_string($condition) || is_numeric($condition)) {
			$values = explode(',', trim($condition));
		} elseif (is_array($condition)) {
			$values = $condition;
		} else {
			Error::show('fault_primary_value_format');
		}

		if (count($this->_primaries) == count($values)) {
			$where = $this->map(array_combine($this->_primaries, $values));
		} else {
			Error::show('fault_primary_num');
		}

		return $where;
	}

	/**
	 * 查询多条记录
	 * @param string|array $condition
	 * @param string|array $option
	 * @param bool $debug
	 */
	public function find($condition = false, $option = false, $debug = false)
	{
		return $this->_find($condition, $option, $debug, false);
	}

	/**
	 * 查询一条记录
	 * @param string|array $condition
	 * @param string|array $option
	 * @param bool $debug
	 */
	public function findFirst($condition = false, $option = false, $debug = false)
	{
		return $this->_find($condition, $option, $debug, true);
	}

	/**
	 * 获取某个字段值
	 * @param string $field
	 * @param string|array $condition
	 * @param bool $debug
	 */
	public function findValue($field, $condition = false, $debug = false)
	{
		$row = $this->findFirst($condition, $field, $debug);

		if ($debug === DatabaseBase::DEBUG_RETURN) return $row;

		if (is_object($row)) {
			return property_exists($row, $field) ? $row->$field : null;
		}

		$row = (array)$row;
		return isset($row[$field]) ? $row[$field] : OC_EMPTY;
	}

	/**
	 * 查询总数
	 * @param boolean $debug
	 */
	public function getTotal($debug = false)
	{
		if (ocGet('option.group', $this->_sql)) {
			$result = $this->_find(false, false, $debug, false, true);
			return $debug === DatabaseBase::DEBUG_RETURN ? $result : count($result);
		} else {
			$result = $this->_find(false, false, $debug, true, true);
			if ($debug === DatabaseBase::DEBUG_RETURN) return $result;
			return $result ? $result['total'] : 0;
		}
	}

	/**
	 * 查询数据
	 * @param string|array $condition
	 * @param string|array $option
	 * @param bool $debug
	 * @param bool $queryRow
	 * @param bool $count
	 */
	private function _find($condition, $option, $debug, $queryRow, $count = false)
	{
		if ($condition) $this->where($condition);
		if ($queryRow) {
			if (!empty($this->_sql['option']['limit'])) {
				$this->_sql['option']['limit'][1] = 1;
			} else {
				$this->limit(1);
			}
		}

		if ($option) {
			if (ocScalar($option)) {
				$option = array('fields' => $option);
			}
			foreach ($option as $key => $value) {
				$this->_sql['option'][$key] = $value;
			}
		}

		$this->connect(false);
		$fields = $count ? $this->connect(false)->getCountSql('1', 'total') : false;
		$sql = $this->_genSql(true, $fields, $count);

		$cacheInfo = null;
		if (isset($this->_sql['cache']) && is_array($this->_sql['cache'])) {
			$cacheInfo = $this->_sql['cache'];
		}

		list($cacheConnect, $cacheRequired) = $cacheInfo;
		$ifCache = empty($debug) && $cacheConnect;

		if ($ifCache) {
			$sqlEncode = md5($sql);
			$cacheObj  = Cache::connect($cacheConnect, $cacheRequired);
			$cacheData = $this->_getCacheData($sql, $sqlEncode, $cacheObj, $cacheRequired);
			if ($cacheData) return $cacheData;
		}

		if ($queryRow) {
			$result = $this->_driver->queryRow($sql, $debug, $count);
		} else {
			$result = $this->_driver->query($sql, $debug, true, true, false, $count);
		}

		if ($debug === DatabaseBase::DEBUG_RETURN) {
			return $result;
		}

		if (!$count && ocGet('option.page', $this->_sql)) {
			$result = array('total' => $this->getTotal($debug), 'data'	=> $result);
		}

		if ($ifCache && is_object($cacheObj)) {
			$this->_saveCacheData($cacheObj, $sql, $sqlEncode, $cacheRequired, $result);
		}

		$this->_selected = false;
		return $result;
	}

	/**
	 * 连接数据库
	 * @param bool $master
	 */
	public function connect($master = true)
	{
		$this->_driver = null;

		if (!($master || ocGet('option.master', $this->_sql))) {
			if (!is_object($this->_slave)) {
				$this->_slave = DefaultDatabase::factory($this->_server, false, false);
			}
			$this->_driver = $this->_slave;
		}

		if (!is_object($this->_driver)) {
			if (!is_object($this->_master)) {
				$this->_master = DefaultDatabase::factory($this->_server);
			}
			$this->_driver = $this->_master;
		}

		if ($this->_database) {
			$this->_driver->selectDatabase($this->_database);
		}

		$this->_driver->setDataType($this->_dataType);

		return $this->_driver;
	}

	/**
	 * 获取缓存数据
	 * @param object $cacheObj
	 * @param string $sql
	 * @param string $sqlEncode
	 * @param bool $cacheRequired
	 */
	public function _getCacheData($cacheObj, $sql, $sqlEncode, $cacheRequired)
	{
		if (is_object($cacheObj)) {
			if ($callback = ocConfig('CALLBACK.model.query.get_cache_data', null)) {
				$params = array($cacheObj, $sql, $cacheRequired);
				if ($result = Call::run($callback, $params)) {
					return $result;
				}
			} else {
				if ($cacheData = $cacheObj->getVar($sqlEncode)) {
					return json_decode($cacheData);
				}
			}
		}

		return null;
	}

	/**
	 * 保存缓存数据
	 * @param object $cacheObj
	 * @param string $sql
	 * @param string $sqlEncode
	 * @param bool $cacheRequired
	 * @param array $result
	 */
	public function _saveCacheData($cacheObj, $sql, $sqlEncode, $cacheRequired, $result)
	{
		if ($callback = ocConfig('CALLBACK.model.query.save_cache_data', null)) {
			$params = array($cacheObj, $sql, $result, $cacheRequired);
			Call::run($callback, $params);
		} else {
			$cacheObj->setVar($sqlEncode, json_encode($result));
		}
	}

	/**
	 * 左联接
	 * @param string $table
	 * @param string $alias
	 * @param string $on
	 */
	public function leftJoin($table, $alias, $on)
	{
		return $this->_join('left', $table, $alias, $on);
	}

	/**
	 * 右联接
	 * @param string $table
	 * @param string $alias
	 * @param string $on
	 */
	public function rightJoin($table, $alias, $on)
	{
		return $this->_join('right', $table, $alias, $on);
	}

	/**
	 * 全联接
	 * @param string $table
	 * @param string $alias
	 * @param string $on
	 */
	public function innerJoin($table, $alias, $on)
	{
		return $this->_join('inner', $table, $alias, $on);
	}

	/**
	 * 解析on参数
	 * @param string $alias
	 * @param string $on
	 */
	public function parseOn($alias, $on)
	{
		$a = $this->_sql['tables'][$this->_tableName]['alias'];

		if (is_string($on)) {
			$on = str_replace('@.', $alias . '.', str_replace('#.', $a . '.', $on));
		} elseif (is_array($on)) {
			$on = $this->_driver->parseCondition($on, 'AND', '=', $alias);
		}

		return $on;
	}

	/**
	 * 解析fields参数
	 * @param string $alias
	 * @param string $fields
	 */
	public function parseField($alias, $fields)
	{
		$_field = explode(',', $fields);

		foreach ($_field as $key => $value) {
			$value = explode('.', ltrim($value));
			$field = trim($value[count($value) - 1]);
			$_field[$key] = $this->_driver->getFieldNameSql($field, true, $alias);
		}

		return implode(',', $_field);
	}

	/**
	 * 附加当前表别名
	 * @param string $alias
	 */
	public function alias($alias)
	{
		$this->_addAlias($alias, $this->_tableName);
		return $this;
	}

	/**
	 * 附加别名
	 * @param string $alias
	 * @param string $table
	 */
	private function _addAlias($alias, $table)
	{
		if ($alias) {
			$this->_sql['tables'][$this->_getTable($table)]['alias'] = $alias;
		}

		return $this;
	}

	/**
	 * 附加字段
	 * @param string|array $fields
	 * @param string $table
	 */
	public function fields($fields, $table = false)
	{
		if ($fields) {
			if ($table) $table = $this->_getTable($table);
			$fields = array($table, $fields);
			$this->_sql['option']['fields'][] = $fields;
		}

		return $this;
	}

	/**
	 * 附加联接关系
	 * @param string $on
	 * @param string $table
	 */
	private function _addOn($on, $table = false)
	{
		$this->_sql['tables'][$this->_getTable($table)]['on'] = $on;
		return $this;
	}

	/**
	 * 生成Between条件
	 * @param string $field
	 * @param string|integer $value1
	 * @param string|integer $value2
	 * @param string $table
	 */
	public function between($field, $value1, $value2, $table = false)
	{
		$where = array($table, 'between', array($field, $value1, $value2));
		$this->_sql['option']['where'][] = $where;

		return $this;
	}

	/**
	 * 添加条件
	 * @param string|array $where
	 * @param string $table
	 */
	public function where($where, $table = false)
	{
		if (!ocEmpty($where)) {
			$where = array($table, 'where', $where);
			$this->_sql['option']['where'][] = $where;
		}

		return $this;
	}

	/**
	 * 生成复杂条件
	 * @param string $sign
	 * @param array $where
	 * @param string $table
	 */
	public function cWhere($sign, $where, $table = false)
	{
		if (is_string($where)) {
			$where = array($where => $table);
			$table = ($last = func_get_arg(3)) ? $last : false;
		}

		if (!ocEmpty($where)) {
			$where = array($table, 'cWhere', array($sign, $where));
			$this->_sql['option']['where'][] = $where;
		}

		return $this;
	}

	/**
	 * 更多条件
	 * @param string $where
	 * @param string $link
	 */
	public function whereMore($where, $link = false)
	{
		$link = $link ? $link : 'AND';
		$this->_sql['option']['whereMore'][] = compact('where', 'link');
		return $this;
	}

	/**
	 * 尾部更多SQL语句
	 * @param string $sql
	 */
	public function more($sql)
	{
		$sql = (array)$sql;
		foreach ($sql as $value) {
			$this->_sql['option']['more'][] = $value;
		}
		return $this;
	}

	/**
	 * 分组
	 * @param string $groupBy
	 */
	public function groupBy($groupBy)
	{
		if ($groupBy) {
			$this->_sql['option']['group'] = $groupBy;
		}
		return $this;
	}

	/**
	 * 分组条件
	 * @param string $having
	 */
	public function having($having, $table = false)
	{
		if (!ocEmpty($having)) {
			$having = array($table, 'where', $having);
			$this->_sql['option']['having'][] = $having;
		}

		return $this;
	}

	/**
	 * 附加排序
	 * @param string $orderBy
	 */
	public function orderBy($orderBy)
	{
		if ($orderBy) {
			$this->_sql['option']['order'] = $orderBy;
		}
		return $this;
	}

	/**
	 * 附加Limit
	 * @param string $limit
	 */
	public function limit($offset, $rows = 1)
	{
		if (func_num_args() < 2) {
			$rows = $offset;
			$offset = 0;
		}

		$this->_sql['option']['limit'] = array($offset, $rows);
		return $this;
	}

	/**
	 * 分页处理
	 * @param array $limitInfo
	 */
	public function page(array $limitInfo)
	{
		$this->_sql['option']['page'] = true;
		list($offset, $rows) = $limitInfo;
		return $this->limit($offset, $rows);
	}

	/**
	 * 设置字段别名转换映射
	 * @param $tables
	 */
	private function _getAliasFields($tables)
	{
		$unJoined = count($tables) <= 1;
		$transforms = array();

		if ($unJoined) {
			$map = $this->getConfig('MAP');
			if ($map) {
				$transforms = array($map);
			}
		} else {
			$transforms = array();
			foreach ($tables as $key => $row) {
//				$model = new $key();
//				$transforms[$row['alias']] = $model->getConfig('MAP');
			}
		}

		return $transforms;
	}

	/**
	 * 生成Sql
	 * @param boolean $select
	 * @param string $fields
	 * @param bool $count
	 */
	private function _genSql($select, $fields = false, $count = false)
	{
		$this->_driver->clearParams();

		$where  = array();
		$option = ocGet('option', $this->_sql, array());
		$tables = ocGet('tables', $this->_sql, array());
		$from   = $this->_getFromSql($select, $tables);

		if (empty($fields)) {
			if (isset($option['fields']) && $option['fields']) {
				$fields = $this->_getFieldsSql($option['fields']);
			} else {
				$fields = $this->_driver->getDefaultFieldsSql();
			}
		}

		if (isset($option['where']) && $option['where']) {
			$option['where'] = $this->_getWhereSql($option['where']);
			$where[] = array('where' => $option['where'], 'link' => 'AND');
		}

		if (isset($option['whereMore']) && $option['whereMore']) {
			foreach ($option['whereMore'] as $row) {
				$row['where'] = $this->_driver->parseCondition($row['where']);
				$where[] = $row;
			}
		}

		$option['where'] = $this->_driver->getWhereSql($where);

		if (isset($option['limit'])) {
			if ($count) {
				ocDel($option, 'limit');
			} else {
				$option['limit'] = $this->_driver->getLimitSql($option['limit']);
			}
		}

		if (isset($option['having'])) {
			$option['having'] = $this->_getWhereSql($option['having']);
		}

		if ($select) {
			$aliasFields = $this->_getAliasFields($tables);
			$fields = $this->_driver->getAliasFieldsSql($fields, $aliasFields);
			return $this->_driver->getSelectSql($fields, $from, $option);
		} else {
			return $option['where'];
		}
	}

	/**
	 * 获取条件SQL语句
	 * @param array $data
	 * @return array
	 */
	private function _getWhereSql(array $data)
	{
		$where = array();

		foreach ($data as $key => $value) {
			list($table, $whereType, $whereData) = $value;
			$alias = false;
			if ($table) {
				$alias = ocGet(array('tables', $table, 'alias'), $this->_sql);
			}
			if ($whereType == 'where') {
				if (is_array($whereData)) {
					$whereData = $this->map($whereData);
				}
				$where[] = $this->_driver->parseCondition(
					$whereData,  'AND', '=', $alias
				);
			} elseif ($whereType == 'between') {
				$where[] = call_user_func_array(array($this->_driver, 'getBetweenSql'), $whereData);
			} else {
				$where[] = $this->_getComplexWhere($whereData, $alias);
			}
		}

		$where = $this->_driver->linkWhere($where);
		$where = $this->_driver->wrapWhere($where);

		return $where;
	}

	/**
	 * 获取字段列表
	 * @param array $fields
	 * @param string $alias
	 */
	private function _getFieldsSql($data, $alias = false)
	{
		if (is_string($data)) {
			return $data;
		}

		$fields = array();
		foreach ($data as $key => $value) {
			list($table, $fieldData) = $value;
			$alias = false;
			if ($table) {
				$alias = ocGet(array('tables', $table, 'alias'), $this->_sql);
				if (is_string($fieldData)) {
					$fieldData = array_map('trim', (explode(',', $fieldData)));
				}
			}
			if (is_array($fieldData)) {
				$fields[] = $this->_driver->getFieldsSql($fieldData, $alias);
			} else {
				$fields[] = $fieldData;
			}
		}

		return $this->_driver->getMultiFieldsSql($fields);
	}

	/**
	 * 生成数据表SQL
	 * @param boolean $select
	 */
	private function _getFromSql($select, $tables)
	{
		$unJoined = count($tables) <= 1;
		$from = null;
		
		foreach ($tables as $key => $param) {
			list($type, $fullname, $alias, $on) = array_fill(0, 4, null);
			extract($param);

			if (empty($fullname)) continue;
			if ($unJoined) $alias = false;

			$on = $this->parseOn($alias, $on);
			$fullname = $this->_driver->getTableName($fullname);

			if ($select) {
				$from = $from . $this->_driver->getJoinSql($type, $fullname, $alias, $on);
			}
		}

		return $from;
	}

	/**
	 * 详细的复杂条件
	 * @param array $where
	 * @param string $alias
	 */
	private function _getComplexWhereDetail($where, $alias)
	{
		$data = array_shift($where);
		$cond = null;

		if (is_string($data) && $where) {
			$data = array_map('trim', explode(OC_DIR_SEP, $data));
			$count = count($data);
			if ($count == 0) {
				Error::show('fault_cond_sign');
			} elseif ($count == 1) {
				$sign = $data[0];
				$link = 'AND';
			} else {
				list($link, $sign) = $data;
			}

			$cond = is_array($where) ? $this->map($where) : $where;
			$cond = $this->_driver->parseCondition($cond, $link, $sign, $alias);
		}

		return $this->_driver->wrapWhere($cond);
	}
	
	/**
	 * 复杂条件
	 * @param array $data
	 * @param string $alias
	 */
	private function _getComplexWhere($data, $alias)
	{
		$cond = null;

		if ($data[1]) {
			if (ocAssoc($data[1])) {
				array_unshift($data[1], $data[0]);
				$cond = $this->_getComplexWhereDetail($data[1], $alias);
			} else {
				$cond = array();
				foreach ($data[1] as $val) {
					$cond[] = $this->_getComplexWhereDetail($val, $alias);
				}
				$cond = $this->_driver->linkWhere($cond, $data[0]);
				$cond = $this->_driver->wrapWhere($cond);
			}
		}

		return $cond;
	}

	/**
	 * 获取表名
	 * @return mixed
	 */
	protected function getTable()
	{
		return $this->_table;
	}

	/**
	 * 获取全表名
	 * @param string $table
	 */
	protected function _getTable($table)
	{
		return empty($table) ? $this->_tableName : $table;
	}
	
	/**
	 * 配置联接
	 * @param string $key
	 * @param string $table
	 * @param string $alias
	 */
	private function _configJoin($key, $table, $alias)
	{
		$on = $fields = false;
		if (!empty($this->_config['JOIN'][$table])) {
			$config = $this->_config['JOIN'][$table];
			if (!empty($config['on'])) $on = $config['on'];
			if (!empty($config['fields'])) $fields = $config['fields'];
		}
		
		$alias = is_string($alias) && $alias ? $alias : $key;
		
		$this->_addAlias($alias, $key)
			 ->_addOn($on, $key)
			 ->fields($fields, $key);
	}
	
	/**
	 * 参数联接
	 * @param string $table
	 * @param string $alias
	 * @param string $on
	 */
	private function _sqlJoin($table, $alias, $on)
	{
		$alias = isset($alias) && is_string($alias) ? $alias : $table;
		$this->_addAlias($alias, $table);

		if ($on) $this->_addOn($on, $table);
	}
	
	/**
	 * 联接查询
	 * @param string $type
	 * @param string $table
	 * @param string $alias
	 * @param string $on
	 */
	private function _join($type, $table, $alias, $on = false)
	{
		$key = $table;

		if (!is_string($table)) {
			Error::show('need_string_table_name');
		}

		if (ocKeyExists('tables.' . $table, $this->_sql)) {
			$key = trim($table) . '_' . $alias;
		}

		$this->_sql['tables'][$key]['type'] = strtoupper($type ? $type . ' JOIN ' : false);
		$this->_sql['tables'][$key]['fullname'] = $table;

		if (empty($on)) {
			$this->_configJoin($key, $table, $alias);
		} else {
			$this->_sqlJoin($key, $alias, $on);
		}

		return $this;
	}

	/**
	 * 获取关联模型
	 * @param string $key
	 * @return mixed
	 */
	public function &__get($key)
	{
		if (isset($this->_config['JOIN'][$key])) {
			if (!isset($this->_relations[$key])) {
				$this->_relations[$key] = $this->_relateFind($key);
			}
			return $this->_relations[$key];
		} else {
			return parent::__get($key);
		}
	}

	/**
	 * 获取关联模型
	 * @param string $key
	 * @return mixed
	 */
	public function __set($key, $value)
	{
		if (isset($this->_config['JOIN'][$key])) {
			$this->_relations[$key] = $value;
			$this->_changes[$key] = &$this->_relations[$key];
		} else {
			return parent::__set($key, $value);
		}
	}

	/**
	 * 关联模型查询
	 * @param $alias
	 * @return null
	 */
	private function _relateFind($alias)
	{
		$config = $this->_getRelateConfig($alias);
		$result = null;

		if ($config) {
			$where = array($config['foreignKey'] => $this->$config['primaryKey']);
			if (in_array($config['joinType'], array('one','manyOne'))) {
				$result = $config['class']::build()
					->where($where)
					->where($config['condition'])
					->selectOne();
			} elseif (in_array($config['joinType'], array('oneMany','many'))) {
				$result = new ObjectRecords($config['class'], array($where, $config['condition']));
				$result->setLimit(0, 0, 1);
			}
		}

		return $result;
	}

	/**
	 * 关联模型数据保存
	 */
	private function _relateSave()
	{
		$changes = array();

		foreach ($this->_changes as $key => $object) {
			$config = $this->_getRelateConfig($key);
			if ($config) {
				$data = array();
				if (in_array($config['joinType'], array('one','manyOne')) && is_object($object)) {
					$data = array($object);
				} elseif (in_array($config['joinType'], array('oneMany','many'))) {
					if (is_object($object)) {
						$data = array($object);
					} elseif (is_array($object)) {
						$data = $object;
					}
				}
				foreach ($data as &$model) {
					if (is_object($model) && $model instanceof \Ocara\ModelBase) {
						$where = array($config['foreignKey'] => $this->$config['primaryKey']);
						$model->$config['foreignKey'] = $this->$config['primaryKey'];
						$model->where($where)->where($config['condition'])->save();
					}
				}
				$changes[$key] = $data;
			}
		}

		foreach ($changes as $key => $value) {
			foreach ($data as $model) {
				if (is_object($model) && $model instanceof \Ocara\ModelBase) {
					$model->db()->transCommit();
				}
			}
		}

		$this->db()->transCommit();

		return true;
	}

	/**
	 * 获取关联配置
	 * @param $key
	 * @return mixed
	 */
	private function _getRelateConfig($key)
	{
		$config = $this->_config['JOIN'][$key];

		if (count($config) < 3) {
			OCError::show('fault_relate_config');
		}

		list($joinType, $primaryKey, $relation) = $config;
		$condition = isset($config[4]) ? $config[4]: null;

		if (is_array($relation)) {
			list($class, $foreignKey) = $relation;
		} else {
			$class = $relation;
			$foreignKey = $primaryKey;
		}

		return compact('joinType', 'primaryKey', 'relation', 'condition', 'class', 'foreignKey');
	}
}
