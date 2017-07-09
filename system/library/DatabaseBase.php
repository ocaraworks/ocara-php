<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 数据库接口基类DatabaseBase
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

class DatabaseBase extends Sql
{
	/**
	 * 调试配置
	 */
	const DEBUG_NO = 0; //非调试
	const DEBUG_RETURN = 1; //返回调试内容
	const DEBUG_PRINT = 2; //用print_r()打印调试信息
	const DEBUG_DUMP = 3; //用var_dump()打印调试信息

	/**
	 * 连接属性
	 */
	protected $_plugin = null;
	protected $_isPdo = false;

	protected $_config;
	protected $_pdoName;
	protected $_prepared;
	protected $_dataType;
	protected $_connectName;

	protected $_params = array();
	protected $_unions = array();

	private $_error = array();
	private static $_connects = array();

	protected static $paramOptions = array(
		'set', 		'where', 	'groupBy',
		'having', 	'limit', 	'orderBy',
		'more',
	);

	/**
	 * 初始化方法
	 * @param array $config
	 */
	public function __construct(array $config)
	{
		$options = array(
			'host', 'port', 'type', 'class', 'pconnect',
			'default', 'username', 'prefix', 'charset',
			'timeout', 'socket', 'options', 'keywords',
		);

		$values = array_fill(0, count($options), OC_EMPTY);
		$config = array_merge(array_combine($options, $values), $config);
		$config['name'] = ocDel($config, 'default');

		if (empty($config['charset'])) {
			$config['charset'] = 'utf8';
		}
		if (empty($config['socket'])) {
			$config['socket'] = null;
		}
		if (empty($config['options'])) {
			$config['options'] = array();
		}
		if (empty($config['prepare'])) {
			$config['prepare'] = true;
		}
		if (empty($config['pconnect'])) {
			$config['pconnect'] = false;
		}
		if (empty($config['keywords'])) {
			$config['keywords'] = array();
		} else {
			$keywords = explode(',', $config['keywords']);
			$config['keywords'] = array_map(
				'trim', array_map('strtolower', $keywords)
			);
		}

		$this->_config = $config;
		ocDel($this->_config, 'password');
		$this->initialize($config);
	}

	/**
	 * 设置连接名称
	 * @param $connectName
	 */
	public function setConnectName($connectName)
	{
		$this->_connectName = $connectName;
	}

	/**
	 * 获取连接名称
	 * @return mixed
	 */
	public function getConnectName()
	{
		return $this->_connectName;
	}

	/**
	 * 清理绑定参数
	 */
	public function clearBindParams()
	{
		$this->_params = array();
	}

	/**
	 * 获取绑定参数
	 * @return array
	 */
	public function getBindParams()
	{
		return $this->_params;
	}

	/**
	 * 合并查询
	 * @param $model
	 * @param bool $unionAll
	 */
	public function union($model, $unionAll = false)
	{
		$this->_unions[] = compact('model', 'unionAll');;
	}

	/**
	 * 初始化设置
	 * @param array $config
	 */
	public function initialize(array $config)
	{
		$config['password'] = ocGet('password', $config);
		$connectName = $config['connect_name'];
		$this->setConnectName($connectName);

		$exists = isset(self::$_connects[$connectName]) && self::$_connects[$connectName] instanceof DriverBase;

		if ($exists) {
			$this->_plugin = self::$_connects[$connectName];
		} else {
			$this->_plugin = $this->getDriver($config);
			self::$_connects[$connectName] = $this->_plugin;
			$this->isPconnect($config['pconnect']);
			$this->_plugin->connect();
			$this->isPrepare($config['prepare']);
		}

		if ($this->_plugin instanceof \Ocara\Database\Driver\PdoDriver) {
			$this->_isPdo = true;
		}

		if (!$exists) {
			$this->setCharset($config['charset']);
		}
	}

	/**
	 * 获取数据库驱动类
	 * @param array $data
	 */
	public function getDriver(array $data)
	{
		if (ocCheckExtension($this->_pdoName, false)) {
			$object = $this->loadDatabase('Pdo');
			$object->initialize($this->getPdoParams($data));
		} else {
			$object = $this->loadDatabase($data['class']);
			$object->initialize($data);
		}

		return $object;
	}

	/**
	 * 加载数据库驱动类
	 * @param string $class
	 */
	public function loadDatabase($class)
	{
		$class = $class . 'Driver';
		$classInfo = ServiceBase::classFileExists("Database/Driver/{$class}.php");

		if ($classInfo) {
			list($path, $namespace) = $classInfo;
			include_once($path);
			$class = $namespace . 'Database\Driver' . OC_NS_SEP . $class;
			if (class_exists($class, false)) {
				$object = new $class();
				return $object;
			}
		}

		$this->showError('not_exists_database');
	}

	/**
	 * 获取配置选项
	 * @param null $name
	 * @return array|bool|mixed|null
	 */
	public function getConfig($name = null)
	{
		if (func_num_args()) {
			if (ocEmpty($name)) {
				return null;
			}
			return ocGet((string)$name, $this->_config);
		}

		return $this->_config;
	}

	/**
	 * 设置结果集返回数据类型
	 * @param string $dataType
	 */
	public function setDataType($dataType)
	{
		$dataType = strtolower($dataType);

		if (empty($dataType)) {
			$dataType = ocConfig('MODEL_QUERY_DATA_TYPE', false);
		}

		$this->_dataType = $dataType == 'object' ? 'object' : 'array';
	}

	/**
	 * 获取合并设置
	 * @return array
	 */
	public function getUnions()
	{
		return $this->_unions;
	}

	/**
	 * 执行SQL语句
	 * @param string $sql
	 * @param bool $debug
	 * @param bool $query
	 * @param bool $required
	 * @param bool $queryRow
	 * @param bool $count
	 */
	public function query($sql, $debug = false, $query = true, $required = true, $queryRow = false, $count = false)
	{
		$ret = $this->_checkDebug($debug, $sql);
		if ($ret) return $ret;

		$sql = trim($sql);
		if ($callback = ocConfig('CALLBACK.database.execute_sql.before', null)) {
			Call::run($callback, array($sql, date(ocConfig('DATE_FORMAT.datetime'))));
		}

		try {
			$params = $this->_params ? array($this->_params) : array();
			if ($query) {
				foreach ($this->_unions as $union) {
					if ($count) {
						$unionData = $union['model']->getTotal(self::DEBUG_RETURN);
					} else {
						$unionData = $union['model']->find(false, false, self::DEBUG_RETURN);
					}
					$sql .= $this->getUnionSql($unionData['sql'], $union['unionAll']);
					$params[] = $unionData['params'];
				}
			}

			if ($this->_prepared && $params) {
				$this->_plugin->prepare($sql);
				$this->_bindParams($params);
				$result = $this->_plugin->execute();
			} else {
				$result = $this->_plugin->query($sql);
			}

			if ($query) {
				if ($count) {
					$result = $this->_plugin->get_result($this->_dataType);
					$total = 0;
					foreach ($result as $row) {
						$total += reset($row);
					}
					$result = array(array('total' => $total));
				} else {
					$result = $this->_plugin->get_result($this->_dataType, $queryRow);
				}
			}
		} catch (\Exception $exception) {
			Error::show($exception->getMessage());
		}

		$ret = $this->checkError($result, $sql, $required);

		return $ret;
	}

	/**
	 * 查询一条记录
	 * @param string $sql
	 * @param bool $debug
	 * @param bool $count
	 */
	public function queryRow($sql, $debug = false, $count = false)
	{
		$result = $this->query($sql, $debug, true, true, true, $count);

		if ($result && empty($debug)) {
			$result = reset($result);
		}

		return $result;
	}

	/**
	 * 是否预处理
	 * @param bool $pconnect
	 */
	public function isPconnect($pconnect = true)
	{
		if (func_num_args()) {
			$this->_pconnect = $pconnect ? true : false;
			$this->_plugin->is_pconnect($pconnect);
		}
		return $this->_pconnect;
	}

	/**
	 * 是否预处理
	 * @param bool $prepare
	 */
	public function isPrepare($prepare = true)
	{
		if (func_num_args()) {
			$this->_prepared = $prepare ? true : false;
			$this->_plugin->is_prepare($prepare);
		}
		return $this->_prepared;
	}

	/**
	 * 绑定参数
	 * @param string $type
	 * @param array $option
	 * @param scalar $params
	 */
	public function bind($option, $type, &$params)
	{
		if (is_string($type)) {
			$type = explode(OC_EMPTY, strtolower($type));
		} elseif (is_array($type)) {
			$type = array_map('strtolower', $type);
		}

		$types = $this->mapParamType($type);
		$data = array();

		foreach ($params as $key => &$value) {
			$dataType = empty($types[$key]) ? $this->parseParamType($value) : $types[$key];
			$data[] = array('type' => $dataType, 'value' => $value);
		}

		$option = strtolower($option);
		$this->_params[$option] = array_merge($this->_params[$option], $data);
	}

	/**
	 * 扩展函数（字段类型映射）
	 * @param array $types
	 * @return array
	 */
	protected function mapParamType($types)
	{
		return array();
	}

	/**
	 * 绑定参数
	 * @param array $params
	 */
	private function _bindParams(array $params)
	{
		$types = false;
		$data = array();
		$paramData = array();

		foreach ($params as $row) {
			foreach (self::$paramOptions as $option) {
				if (!empty($row[$option])) {
					$paramData = array_merge($paramData, $row[$option]);
				}
			}
		}

		foreach ($paramData as $key => &$value) {
			$type = $this->parseParamType($value);
			if ($this->_isPdo) {
				$this->_plugin->bind_param($key + 1, $value, $type);
			} else {
				$types = $types . $type;
				$data[] = &$value;
			}
		}

		if (!$this->_isPdo) {
			array_unshift($data, $types);
			call_user_func_array(array($this->_plugin, 'bind_param'), $data);
		}
	}

	/**
	 * 解析参数类型
	 * @param mixed $value
	 */
	private function parseParamType($value)
	{
		$mapTypes = $this->_plugin->get_param_types();

		if (is_numeric($value)) {
			return $mapTypes['integer'];
		} elseif (is_string($value)) {
			return $mapTypes['string'];
		} elseif (is_bool($value)) {
			return $mapTypes['boolean'];
		} else {
			return $mapTypes['string'];
		}
	}

	/**
	 * 获取最后一次插入记录的自增ID
	 * @param string $sql
	 * @param bool $debug
	 */
	public function getInsertId($sql = false, $debug = false)
	{
		if (empty($sql)) $sql = $this->getLastIdSql();
		$result = $this->queryRow($sql, $debug);
		return $result ? $result['id'] : false;
	}

	/**
	 * 检测表是否存在
	 * @param string $table
	 * @param bool $required
	 */
	public function tableExists($table, $required = false)
	{
		$table = $this->getTableFullname($table);
		$sql = $this->getSelectSql(1, $table, array('limit' => 1));
		$ret = $this->query($sql, false, false, false);

		if ($required) {
			return $ret;
		} else {
			return $this->errorExists() == false;
		}
	}

	/**
	 * 插入记录
	 * @param string $table
	 * @param array $data
	 * @param bool $debug
	 */
	public function insert($table, $data = null, $debug = false)
	{
		if (empty($data) || !is_array($data)) {
			$this->showError('fault_save_data');
		}

		$table = $this->getTableFullname($table);
		$sql = $this->getInsertSql($table, $data);

		$ret = $this->_checkDebug($debug, $sql);
		if ($ret) return $ret;

		$insertResult = $data ? $this->query($sql, false, false) : false;

		$this->clearBindParams();
		return $insertResult ? $this->getInsertId() : false;
	}

	/**
	 * 更新记录
	 * @param string $table
	 * @param string|array $data
	 * @param string|array $condition
	 * @param bool $debug
	 */
	public function update($table, $data = false, $condition = null, $debug = false)
	{
		if (empty($data)) {
			$this->showError('fault_save_data');
		}

		$table = $this->getTableFullname($table);
		$condition = $this->parseCondition($condition);
		$sql = $this->getUpdateSql($table, $data, $condition);

		$ret = $this->_checkDebug($debug, $sql);
		if ($ret) return $ret;

		$ret = $data ? $this->query($sql, $debug, false) : false;

		$this->clearBindParams();
		return $ret;
	}

	/**
	 * 删除记录
	 * @param string $table
	 * @param string|array $condition
	 * @param bool $debug
	 */
	public function delete($table, $condition, $debug = false)
	{
		$table = $this->getTableFullname($table);
		$condition = $this->parseCondition($condition);
		$sql = $this->getDeleteSql($table, $condition);

		$ret = $this->query($sql, $debug, false);
		$this->clearBindParams();

		return $ret;
	}

	/**
	 * 获取表全名
	 * @param string $table
	 */
	public function getTableFullname($table)
	{
		if (preg_match('/^' . OC_SQL_TAG . '(.*)$/i', $table, $mt)) {
			return $mt[1];
		}

		if (preg_match('/(\w+)\.(\w+)/i', $table, $mt)) {
			$databaseName = $mt[1];
			$table = $mt[2];
		} else {
			$databaseName = $this->_config['name'];
			if ($this->_config['prefix']) {
				$table = $this->_config['prefix'] . $table;
			}
		}

		return $this->getTableNameSql($databaseName, $table);
	}

	/**
	 * 选择数据库
	 * @param string $name
	 */
	public function selectDatabase($name)
	{
		$result = $this->selectDb($name);

		if ($result) {
			$this->_config['name'] = $name;
		} else {
			$this->showError('failed_select_database');
		}

		return $result;
	}

	/**
	 * 获取关键字
	 */
	public function getKeywords()
	{
		return $this->_config['keywords'] ? $this->_config['keywords'] : array();
	}

	/**
	 * 事务开始
	 */
	public function beginTransaction()
	{
		$this->autocommit(false);
		$result = $this->_plugin->begin_transaction();
		return $result;
	}

	/**
	 * ::TODO 事务隔离级别设置
	 */
	public function setTransactionLevel()
	{
		return true;
	}

	/**
	 * 是否自动提交事务
	 * @param bool $autocommit
	 * @return mixed
	 */
	public function autocommit($autocommit = true)
	{
		$result = $this->_plugin->autocommit($autocommit);
		return $result;
	}

	/**
	 * 事务提交
	 */
	public function commit()
	{
		$result = $this->_plugin->commit();
		$this->autocommit(true);
		return $result;
	}

	/**
	 * 事务回滚
	 */
	public function rollback()
	{
		$result = $this->_plugin->rollback();
		$this->autocommit(true);
		return $result;
	}

	/**
	 * 保存错误信息
	 */
	public function setError()
	{
		$this->_error = array();

		if ($this->_plugin->error_no() > 0) {
			$this->_error['errorCode'] = $this->_plugin->error_no();
			$this->_error['errorMessage'] = $this->_plugin->error();
			$this->_error['errorList'] = $this->_plugin->error_list();
		}
	}

	/**
	 * 获取错误代码
	 */
	public function getErrorCode()
	{
		return ocGet('errorCode', $this->_error);
	}

	/**
	 * 获取错误信息
	 */
	public function getError()
	{
		return ocGet('errorMessage', $this->_error);
	}

	/**
	 * 获取错误列表
	 */
	public function getErrorList()
	{
		return ocGet('errorList', $this->_error);
	}

	/**
	 * 检测是否出错
	 */
	public function errorExists()
	{
		return (boolean)$this->_error;
	}

	/**
	 * 显示错误信息
	 */
	public function showError()
	{
		if ($this->errorExists()) {
			$error = $this->_error;
			$this->_error = $error;
			Error::show($this->getError());
		}
	}

	/**
	 * 检测错误
	 * @param array|object $ret
	 * @param stirng $sql
	 * @param bool $required
	 */
	public function checkError($ret, $sql, $required = true)
	{
		$this->setError();
		$errorExists = $this->errorExists();
		$error = $errorExists ? $this->getError() : null;

		if ($sql) {
			$callback = ocConfig('CALLBACK.database.execute_sql.after', false);
			if ($callback) {
				$params = array($sql, $errorExists, $error,$ret, date(ocConfig('DATE_FORMAT.datetime')));
				Call::run($callback, $params);
			}
		}

		if ($required && $errorExists) {
			return $this->showError($error);
		}

		return $ret;
	}

	/**
	 * debug参数检查
	 * @param boolean $debug
	 * @param string $sql
	 */
	private function _checkDebug($debug, $sql)
	{
		if ($debug) {
			$ret = array('sql' => $sql, 'params' => $this->_params);
			$this->_params = array();
			if ($debug === self::DEBUG_RETURN) {
				return $ret;
			}
			if ($debug === self::DEBUG_PRINT
				|| $debug === self::DEBUG_DUMP
			) {
				if (OC_SYS_MODEL == 'develop') {
					if ($debug === self::DEBUG_DUMP) {
						ocDump($ret);
					} else {
						ocPrint($ret);
					}
				} else {
					$this->showError('invalid_debug');
				}
			} else {
				$this->showError('fault_debug_param');
			}
		}
		
		return false;
	}
}