<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  PDO数据对象驱动类Database_Driver_OCPdo
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Database\Driver;
use \PDO;
use Ocara\DriverBase;
use Ocara\Error;
use Ocara\Interfaces\Driver as DriverInterface;

defined('OC_PATH') or exit('Forbidden!');

class PdoDriver extends DriverBase implements DriverInterface
{
	/**
	 * PDO绑定参数类型映射
	 */
	protected $_paramTypesMap = array(
		'integer' => PDO::PARAM_INT,
		'string'  => PDO::PARAM_STR,
        'binary'  => PDO::PARAM_LOB,
		'boolean' => PDO::PARAM_BOOL
	);

	/**
	 * 初始化配置
	 * @param array $config
	 */
	public function initialize($config)
	{
		$this->_config = $config;
	}

	/**
	 * @return mixed
	 * @throws \Ocara\Exception
	 */
	public function connect()
	{
		$limitConnect = ocConfig('DATABASE_LIMIT_CONNECT_TIMES', 3);

		for ($i = 1; $i <= $limitConnect; $i++) {
			$error = $this->_connect();
			if ($error) {
				if ($i < $limitConnect) continue;
				Error::show('failed_db_connect', $error);
			} else {
				break;
			}
		}

		return $this->_connection = $this->_instance;
	}

	/**
	 * 连接数据库
	 */
	protected function _connect()
	{
		$error = array();
		$options = $this->_config['options'];

		if ($this->_pconnect) {
			$options[PDO::ATTR_PERSISTENT] = true;
		}

		try {
			$this->_instance = new PDO(
				$this->_config['dsn'], $this->_config['username'],
				$this->_config['password'], $options
			);
		} catch (\PDOException $e) {
			$this->_errno = $e->getCode();
			$this->_error = $e->getMessage();
			$error = array(
				$this->_config['name'], $this->_errno, $this->_error
			);
		}

		return $error;
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
	 * @param $name
	 */
	public function select_db($name)
	{
		return;
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
	public function query($sql, $resultmode = PDO::FETCH_ASSOC)
	{
		return $this->_stmt = $this->_instance->query($sql, $resultmode);
	}

	public function close()
	{}

	/**
	 * @return array
	 */
	public function fetch_array()
	{
		return array_values($this->_stmt->fetchAll());
	}

	/**
	 * @return mixed
	 */
	public function fetch_assoc()
	{
		return $this->_stmt->fetchAll();;
	}

	/**
	 * @return mixed
	 */
	public function fetch_row()
	{
		return $this->_stmt->fetch();
	}

	/**
	 * @return mixed
	 */
	public function fetch_object()
	{
		return $this->_stmt->fetchObject();
	}

	public function free_result()
	{}

	/**
	 * @return mixed
	 */
	public function num_rows()
	{
		return $this->_stmt->rowCount();
	}

	/**
	 * @param int $num
	 * @return mixed
	 */
	public function data_seek($num = 0)
	{
		return $this->_stmt->nextRowset();
	}

	/**
	 * @return mixed
	 */
	public function affected_rows()
	{
		return $this->_stmt->rowCount();
	}

	/**
	 * @return null
	 */
	public function error_no()
	{
		if (is_object($this->_stmt)) {
			$errorCode = $this->_stmt->errorCode();
		} else {
			$errorCode = $this->_instance->errorCode();
		}
	
		if (substr($errorCode, -3) == '000') {
			return null;
		}
		
		return $errorCode;
	}

	/**
	 * @return mixed
	 */
	public function error()
	{
		if (is_object($this->_stmt)) {
			$errorList = $this->_stmt->errorInfo();
		} else {
			$errorList = $this->_instance->errorInfo();
		}
		
		return end($errorList);
	}

	/**
	 * @return mixed
	 */
	public function error_list()
	{
		return $this->_stmt->errorInfo();
	}

	/**
	 * @param $sql
	 * @return mixed
	 */
	public function show_fields($sql)
	{
		return $this->_instance->query($sql);
	}

	/**
	 * @param string $str
	 * @return string
	 */
	public function real_escape_string($str)
	{
		return $str;
	}
	
	/**
	 * 预处理
	 * @param string $sql
	 */
	public function prepare($sql)
	{
		return $this->_stmt = $this->_instance->prepare($sql);
	}

	/**
	 * 绑定参数
	 * @param string $parameter
	 * @param scalar $variable
	 */
	public function bind_param($parameter, &$variable)
	{
		return call_user_func_array(array($this->_stmt, 'bindParam'), func_get_args());
	}
	
	/**
	 * 获取结果集数据
	 * @param string $dataType
	 * @param bool $queryRow
	 */
	public function get_result($dataType, $queryRow = false)
	{
		$isObject = $dataType == 'object';
		$result = $isObject ? null : array();

		if (is_object($this->_stmt)) {
			while (true) {
				if ($isObject) {
					$row = $this->_stmt->fetchObject();
				} else {
					$row = $this->_stmt->fetch(PDO::FETCH_ASSOC);
				}
				if (empty($row)) break;
				$result[] = $row;
				if ($queryRow) break;
			}
		}

		return $result;
	}

	/**
	 * @return mixed
	 */
	public function execute()
	{
		return $this->_stmt->execute();
	}

	/**
	 * 找不到方法时
	 * @param string $name
	 * @param array $params
	 * @return mixed
	 */
	public function __call($name, $params)
	{
		if ($this->_instance && method_exists($this->_instance, $name)) {
			return call_user_func_array(array($this->_instance, $name), $params);
		}
		Error::show('no_method', array($name));
	}
}

