<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  PDO数据对象驱动类PdoDriver
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Databases\Driver;

use \PDO;
use \PDOException;
use Ocara\Exceptions\Exception;
use Ocara\Core\DriverBase;
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
	public function init($config)
	{
		$this->_config = $config;
	}

	/**
	 * @return mixed
	 * @throws \Ocara\Core\Exception
	 */
	public function connect()
	{
		$this->_connect();
		return $this->_connection = $this->_instance;
	}

	/**
	 * 连接数据库
	 */
	protected function _connect()
	{
		$limitConnect = ocConfig('DATABASE_LIMIT_CONNECT_TIMES', 3);

		for ($i = 1; $i <= $limitConnect; $i++) {
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
			} catch (PDOException $e) {
				$this->_errno = $e->getCode();
				$this->_error = $e->getMessage();
				$error = array(
					$this->_config['name'], $this->_errno, $this->_error
				);
			}
			if ($error) {
				if ($i < $limitConnect) continue;
				ocService()->error->show('failed_db_connect', $error);
			} else {
				break;
			}
		}
	}

	/**
	 * 服务器是否断开连接
	 * @return bool
	 */
	public function is_not_active()
	{
		return $this->error_no() == '2006';
	}

	/**
	 * 唤醒连接
	 */
	public function wake_up()
	{
		$this->_connect();
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
	 * @param int $resultmode
	 * @return mixed
	 */
	public function query($sql, $resultmode = PDO::FETCH_ASSOC)
	{
		return $this->_stmt = $this->_instance->query($sql, $resultmode);
	}

	public function close()
	{}

	/**
	 * 开始一个事务
	 * @return mixed
	 */
	public function begin_transaction()
	{
		return $this->_instance->beginTransaction();
	}

	/**
	 * 检查驱动内的一个事务当前是否处于激活
	 */
	public function in_transaction()
	{
		return $this->_instance->inTransaction();
	}

	/**
	 * 提交事务
	 * @return mixed
	 */
	public function commit()
	{
		return $this->_instance->commit();
	}

	/**
	 * 回退事务
	 * @return mixed
	 */
	public function rollBack()
	{
		return $this->_instance->rollBack();
	}

	/**
	 * 设置是否自动提交事务
	 * @param bool $autocommit
	 */
	public function autocommit($autocommit = true)
	{
		$autocommit = $autocommit ? 1 : 0;
		return $this->_instance->setAttribute(\PDO::ATTR_AUTOCOMMIT, $autocommit);
	}

	/**
	 * 获取参数
	 * @param mixed $name
	 * @return mixed
	 */
	public function get_attribute($name)
	{
		return $this->_instance->getAttribute($name);
	}

	/**
	 * 设置参数
	 * @param mixed name
	 * @return mixed
	 */
	public function set_attribute($name)
	{
		return $this->_instance->getAttribute($name);
	}

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
	public function fetch_object()
	{
		return $this->_stmt->fetchObject();
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

		return (integer)$errorCode;
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
		if (is_object($this->_stmt)) {
			$errorList = $this->_stmt->errorInfo();
		} else {
			$errorList = $this->_instance->errorInfo();
		}

		return $errorList;
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
	 * 绑定参数
	 * @param string $name
	 * @param mixed $value
	 * @param int $type
	 * @return mixed
	 */
	public function bind_value($name, $value, $type = PDO::PARAM_STR)
	{
		return $this->_stmt->bindValue($name, $value, $type);
	}

	/**
	 * 返回绑定参数信息
	 */
	public function debugDumpParams()
	{
		ob_start();
		$this->_stmt->debugDumpParams();
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

	/**
	 * 获取结果集数据
	 * @param string $dataType
	 * @param bool $queryRow
	 */
	public function get_result($dataType = 'array', $queryRow = false)
	{
		$result = array();

		if (is_object($this->_stmt)) {
            if ($dataType == 'object') {
                while ($row = $this->_stmt->fetchObject()) {
                    $result[] = $row;
                    if ($queryRow) break;
                }
            } elseif ($dataType == 'array') {
                while ($row = $this->_stmt->fetch(PDO::FETCH_ASSOC)) {
                    $result[] = $row;
                    if ($queryRow) break;
                }
            } else {
                if (class_exists($dataType)) {
                    while ($row = $this->_stmt->fetch(PDO::FETCH_ASSOC)) {
                        $row = new $dataType($row);
                        $result[] = $row;
                        if ($queryRow) break;
                    }
                }
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
		parent::_call($name, $params);
	}
}

