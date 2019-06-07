<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Mysql数据库扩展驱动类MysqliDriver
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Databases\Driver;

use \mysqli;
use Ocara\Exceptions\Exception;
use Ocara\Core\DriverBase;
use Ocara\Interfaces\Driver as DriverInterface;

defined('OC_PATH') or exit('Forbidden!');

class MysqliDriver extends DriverBase implements DriverInterface
{
	/**
	 * PDO绑定参数类型映射
	 */
	protected $quoteBackList = array(
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
	 * 连接数据库
	 * @return mixed
	 * @throws Exception
	 */
	public function connect()
	{
		$this->_connect();

		if (empty($this->_connection)) {
			$this->_errno = $this->_instance->connect_errno;
			$this->_error = $this->_instance->connect_error;
			$error = array(
				$this->_config['name'], $this->_errno, $this->_error
			);
			ocService()->error->show('failed_db_connect', $error);
		}

		return $this->_connection;
	}

    /**
     * 获取驱动类型
     * @return mixed
     */
    public function driveType()
    {
        return 'mysql';
    }

	/**
	 * 使用mysqli类连接
	 * @throws Exception
	 */
	protected function _connect()
	{
		$service = ocService();
		$host = ($this->_pconnect ? 'p:' : OC_EMPTY) . $this->_config['host'];
		$args = array(
			$host, $this->_config['username'],
			$this->_config['password'], $this->_config['name'],
			$this->_config['port'],     $this->_config['socket'],
		);

		if (!class_exists('mysqli', false)) {
			$service->error->show('not_exists_class', array('mysqli'));
		}

		$limitConnect = ocConfig('DATABASE_LIMIT_CONNECT_TIMES', 3);

		for($i = 1; $i <= $limitConnect; $i++) {
			try {
				$this->_instance = new mysqli();
				if (empty($this->_instance)) {
					ocService()->error->show('failed_db_init');
				}

				$timeout = $this->_config['timeout'];
				if ($timeout){
					$result = $this->_instance->options(MYSQLI_OPT_CONNECT_TIMEOUT, $timeout);
					if (!$result) {
						$service->error->show('failed_db_set_timeout');
					}
				}

				error_reporting(0);
				$connect = call_user_func_array(array($this->_instance, 'real_connect'), $args);
				if ($connect) {
					$this->_connection = $this->_instance;
					$this->_stmt = $this->_instance->stmt_init();
				}
			} catch (\Exception $exception) {
				if ($i < $limitConnect) continue;
				$this->_errno = $exception->getCode();
				$this->_error = $exception->getMessage();
				$error = array(
					$this->_config['name'], $this->_errno, $this->_error
				);
				$service->error->show('failed_db_connect', $error);
			}
			break;
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
     * @param int|null $resultmode
     * @return mixed
     */
    public function query($sql, $resultmode = MYSQLI_STORE_RESULT)
    {
        return $this->_connection->query($sql);
    }

	/**
	 * @param string $sql
	 * @param int $resultmode
	 * @return mixed
	 */
	public function query_sql($sql, $resultmode = MYSQLI_STORE_RESULT)
	{
		$this->_recordSet = $this->query($sql);
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

		return $result ? : array();
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
		return array($this->_connection->error);
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
     * 预处理
     * @param string $sql
     * @return mixed
     */
    public function prepare_sql($sql)
    {
        return $this->prepare($sql);
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
     * @param string $parameter
     * @param mixed $variable
     */
    public function param_count()
    {
        return $this->_stmt->param_count;
    }

    /**
     * @return mixed
     */
    public function next_result(){
        return $this->_stmt->next_result();
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
     * @return mixed
     */
    public function get_result(){
        return $this->_stmt->get_result();
    }

    /**
     * @return mixed
     */
    public function store_result(){
        return $this->_stmt->store_result();
    }

	/**
	 * 执行SQL
	 */
	public function execute()
	{
		return $this->_stmt->execute();
    }

    /**
     * 执行SQL
     */
    public function execute_sql()
    {
		$result = $this->execute();
		$this->_recordSet = $this->get_result();
		$this->free_result();
		return $result;
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

		parent::_call($name, $params);
	}
}
?>
