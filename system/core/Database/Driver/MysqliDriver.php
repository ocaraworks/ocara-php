<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Mysql数据库扩展驱动类MysqliDriver
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Database\Driver;
use \mysqli;
use Ocara\Exception\Exception;
use Ocara\DriverBase;
use Ocara\Error;
use Ocara\Interfaces\Driver as DriverInterface;

defined('OC_PATH') or exit('Forbidden!');

class MysqliDriver extends DriverBase implements DriverInterface
{
	/**
	 * PDO绑定参数类型映射
	 */
	protected $_paramTypesMap = array(
		 'integer' => 'i',
	     'string'  => 's',
         'binary'  => 's',
		 'boolean' => 'i',
	);

	/**
	 * 初始化配置
	 * @param array $config
	 */
	public function init($config)
	{
		$this->_config = $config;
	}

	/**
	 * @return mixed
	 * @throws \Ocara\Exception
	 */
	public function connect()
	{
		$host = ($this->_pconnect ? 'p:' : OC_EMPTY) . $this->_config['host'];
		$args = array(
			$host, $this->_config['username'],
			$this->_config['password'], $this->_config['name'],
			$this->_config['port'],     $this->_config['socket'],
		);

		if (!class_exists('mysqli', false)) {
			Error::show('not_exists_class', array('mysqli'));
		}

		$limitConnect = ocConfig('DATABASE_LIMIT_CONNECT_TIMES', 3);
		for($i = 1; $i <= $limitConnect; $i++) {
			try {
				$this->_connect($args);
			} catch (Exception $exception) {
				if ($i < $limitConnect) continue;
				$this->_errno = $exception->getCode();
				$this->_error = $exception->getMessage();
				$error = array(
					$this->_config['name'], $this->_errno, $this->_error
				);
				Error::show('failed_db_connect', $error);
			}
			break;
		}

		if (empty($this->_connection)) {
			$this->_errno = $this->_instance->connect_errno;
			$this->_error = $this->_instance->connect_error;
			$error = array(
				$this->_config['name'], $this->_errno, $this->_error
			);
			Error::show('failed_db_connect', $error);
		}

		return $this->_connection;
	}

	/**
	 * 使用mysqli类连接
	 * @param array $args
	 */
	protected function _connect($args)
	{
		$this->_instance = new mysqli();
		if (empty($this->_instance)) {
			Error::show('failed_db_init');
		}

		$timeout = $this->_config['timeout'];
		if ($timeout){
			$result = $this->_instance->options(MYSQLI_OPT_CONNECT_TIMEOUT, $timeout);
			if (!$result) {
				Error::show('failed_db_set_timeout');
			}
		}

		error_reporting(0);
		$connect = call_user_func_array(array($this->_instance, 'real_connect'), $args);
		if ($connect) {
			$this->_connection = $this->_instance;
			$this->_stmt = $this->_instance->stmt_init();
		}
	}

	/**
	 * 获取连接句柄
	 */
	public function connection()
	{
		return $this->_connection;
	}

	/**
	 * 选择数据库
	 * @param string $name
	 */
	public function select_db($name)
	{
		return $this->_connection->select_db($name);
	}

	/**
	 * 获取Statement对象
	 */
	public function stmt()
	{
		return $this->_stmt;
	}

	/**
	 * @param string $sql
	 * @param bool|int $resultmode
	 * @return mixed
	 */
	public function query($sql, $resultmode = MYSQLI_STORE_RESULT)
	{
		$this->_recordSet = $this->_connection->query($sql);
		return $this->_recordSet;
	}

	/**
	 * @return mixed
	 */
	public function close()
	{
		if ($this->_prepared) {
		 	return $this->_stmt->close();
		}
		$this->_stmt->close();
		$this->_connection->close();
	}


	/**
	 * 开始一个事务
	 * @return mixed
	 */
	public function begin_transaction()
	{
		return $this->_connection->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
	}

	/**
	 * 提交事务
	 * @return mixed
	 */
	public function commit()
	{
		return $this->_connection->commit();
	}

	/**
	 * 回退事务
	 * @return mixed
	 */
	public function rollBack()
	{
		return $this->_connection->rollBack();
	}

	/**
	 * 设置是否自动提交事务
	 * @param bool $autocommit
	 */
	public function autocommit($autocommit = true)
	{
		return $this->_connection->setAttribute(\PDO::ATTR_AUTOCOMMIT, $autocommit);
	}

	/**
	 * @return array
	 */
	public function fetch_array()
	{
		return $this->_recordSet ? $this->_recordSet->fetch_array(MYSQLI_ASSOC) : array();
	}

	/**
	 * 取一条Object结果
	 */
	public function fetch_object()
	{
		return $this->_recordSet ? $this->_recordSet->fetch_object() : array();
	}

	/**
	 * @return array
	 */
	public function fetch_assoc()
	{
		return $this->_recordSet ? $this->_recordSet->fetch_assoc() : array();
	}

	/**
	 * 取所有记录
	 */
	public function fetch_all($resultmode = MYSQLI_ASSOC)
	{
		$result = array();

		if ($this->_recordSet) {
			$result = $this->_recordSet->fetch_all($resultmode);
		}

		return $result ? $result : array();
	}

	/**
	 * @return array
	 */
	public function fetch_row()
	{
		return $this->_recordSet ? $this->_recordSet->fetch_row() : array();
	}

	public function free_result()
	{
		if ($this->_prepared) {
			$this->_stmt->free_result();
		}
		$this->_recordSet = null;
	}

	/**
	 * @return int
	 */
	public function num_rows()
	{
		return $this->_recordSet ? $this->_recordSet->num_rows : 0;
	}

	/**
	 * @param int $num
	 * @return bool
	 */
	public function data_seek($num = 0)
	{
		return $this->_recordSet ? $this->_recordSet->data_seek($num) : false;
	}

	/**
	 * @return int
	 */
	public function affected_rows()
	{
		return $this->_recordSet ? $this->_connection->affected_rows : 0;
	}

	/**
	 * @return mixed
	 */
	public function error_no()
	{
		if ($this->_prepared) {
			$errorCode = $this->_stmt->errno;
		} else {
			$errorCode = $this->_connection->errno;
		}

		return (integer)$errorCode;
	}

	/**
	 * @return mixed
	 */
	public function error()
	{
		if ($this->_prepared) {
			return $this->_stmt->error;
		}
		return $this->_connection->error;
	}

	/**
	 * @return mixed
	 */
	public function error_list()
	{
		if ($this->_prepared) {
			return $this->_stmt->error_list;
		}
		return $this->_connection->error_list;
	}

	/**
	 * @param string $str
	 * @return mixed
	 */
	public function real_escape_string($str)
	{
		return $this->_connection->real_escape_string($str);
	}

	/**
	 * @param $charset
	 * @return mixed
	 */
	public function set_charset($charset)
	{
		return $this->_connection->set_charset($charset);
	}

	/**
	 * @param $sql
	 * @return mixed
	 */
	public function show_fields($sql)
	{
		return $this->_connection->query($sql);
	}

	/**
	 * 预处理
	 * @param string $sql
	 * @return mixed
	 */
	public function prepare($sql)
	{
		$result = $this->_stmt->prepare($sql);
		return $result;
	}

	/**
	 * 绑定参数
	 * @param string $parameter
	 * @param mixed $variable
	 */
	public function bind_param($parameter, &$variable)
	{
		$result = call_user_func_array(array($this->_stmt, 'bind_param'), func_get_args());
		return $result;
	}

	/**
	 * 绑定参数
	 * @param mixed $vars
	 */
	public function bind_result(&$vars)	
	{
		$result = call_user_func_array(array($this->_stmt, 'bind_result'), func_get_args());
		return $result;
	}
	
	/**
	 * 执行SQL
	 */
	public function execute()
	{
		$result = $this->_stmt->execute();
		$this->_recordSet = $this->_stmt->get_result();
		$this->_stmt->free_result();
		return $result;
	}

	/**
	 * 设置绑参查询的结果集
	 * @param $resultObj
	 */
	private function _set_record_set($resultObj)
	{
		while ($field = $resultObj->fetch_field()) {
			$params[] = &$row[$field->name];
		}

		call_user_func_array(array($this->_stmt, 'bind_result'), $params);
		$this->_recordSet = array();

		while ($this->_stmt->fetch()) {
			$data = array();
			foreach ($row as $key => $value) {
				$data[$key] = $value;
			}
			$this->_recordSet[] = $data;
		}
	}

	/**
	 * 未知方法调用的处理
	 * @param string $name
	 * @param array $params
	 */
	public function __call($name, $params)
	{
		if ($this->_instance && method_exists($this->_instance, $name)) {
			return call_user_func_array(array($this->_instance, $name), $params);
		}
		
		Error::show('no_method', array($name));
	}
}
?>
