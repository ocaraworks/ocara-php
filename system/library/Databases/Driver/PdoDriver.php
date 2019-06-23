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
	protected $paramTypesMap = array(
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
		$this->config = $config;
	}

    /**
     * @return mixed
     * @throws Exception
     */
	public function connect()
	{
		$this->baseConnect();
		return $this->connection = $this->instance;
	}

    /**
     * 获取驱动类型
     * @return string
     */
    public function driveType()
    {
        return self::DRIVE_TYPE_PDO;
    }

    /**
     * 连接数据库
     * @throws Exception
     */
	protected function baseConnect()
	{
		$limitConnect = ocConfig('DATABASE_LIMIT_CONNECT_TIMES', 3);

		for ($i = 1; $i <= $limitConnect; $i++) {
			$error = array();
			$options = $this->config['options'];

			if ($this->pConnect) {
				$options[PDO::ATTR_PERSISTENT] = true;
			}

			try {
				$this->instance = new PDO(
					$this->config['dsn'], $this->config['username'],
					$this->config['password'], $options
				);
			} catch (PDOException $e) {
				$this->errNo = $e->getCode();
				$this->error = $e->getMessage();
				$error = array(
					$this->config['name'], $this->errNo, $this->error
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
		$this->baseConnect();
	}

    /**
     * 获取连接句柄
     * @return mixed
     */
	public function connection()
	{
		return $this->connection;
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
     * @return mixed
     */
	public function stmt()
	{
		return $this->stmt;
	}

    /**
     * @param string $sql
     * @param null $resultMode
     * @return mixed
     */
	public function query($sql, $resultMode = null)
	{
	    $resultMode = $resultMode ? : PDO::FETCH_ASSOC;
		return $this->instance->query($sql, $resultMode);
	}

    /**
     * @param string $sql
     * @param int $resultMode
     * @return mixed
     */
    public function query_sql($sql, $resultMode = null)
    {
        return $this->stmt = $this->query($sql, $resultMode);
    }

	public function close()
	{}

    /**
     * 开始一个事务
     * @return mixed
     */
	public function begin_transaction()
	{
		return $this->instance->beginTransaction();
	}

    /**
     * 检查驱动内的一个事务当前是否处于激活
     * @return mixed
     */
	public function in_transaction()
	{
		return $this->instance->inTransaction();
	}

    /**
     * 提交事务
     * @return mixed
     */
	public function commit()
	{
		return $this->instance->commit();
	}

	/**
	 * 回退事务
	 * @return mixed
	 */
	public function rollBack()
	{
		return $this->instance->rollBack();
	}

    /**
     * 设置是否自动提交事务
     * @param bool $autocommit
     * @return mixed
     */
	public function autocommit($autocommit = true)
	{
		$autocommit = $autocommit ? 1 : 0;
		return $this->instance->setAttribute(\PDO::ATTR_AUTOCOMMIT, $autocommit);
	}

    /**
     * 获取参数
     * @param $name
     * @return mixed
     */
	public function get_attribute($name)
	{
		return $this->instance->getAttribute($name);
	}

    /**
     * 设置参数
     * @param $name
     * @return mixed
     */
	public function set_attribute($name)
	{
		return $this->instance->getAttribute($name);
	}

	/**
	 * @return array
	 */
	public function fetch_array()
	{
		return array_values($this->stmt->fetchAll());
	}

	/**
	 * @return mixed
	 */
	public function fetch_object()
	{
		return $this->stmt->fetchObject();
	}

	/**
	 * @return mixed
	 */
	public function fetch_assoc()
	{
		return $this->stmt->fetchAll();;
	}

	/**
	 * @return mixed
	 */
	public function fetch_row()
	{
		return $this->stmt->fetch();
	}

	public function free_result()
	{}

	/**
	 * @return mixed
	 */
	public function num_rows()
	{
		return $this->stmt->rowCount();
	}

	/**
	 * @param int $num
	 * @return mixed
	 */
	public function data_seek($num = 0)
	{
		return $this->stmt->nextRowset();
	}

	/**
	 * @return mixed
	 */
	public function affected_rows()
	{
		return $this->stmt->rowCount();
	}

    /**
     * @return int
     */
	public function error_no()
	{
		if (is_object($this->stmt)) {
			$errorCode = $this->stmt->errorCode();
		} else {
			$errorCode = $this->instance->errorCode();
		}

		return (integer)$errorCode;
	}

    /**
     * @return mixed
     */
	public function error()
	{
		if (is_object($this->stmt)) {
			$errorList = $this->stmt->errorInfo();
		} else {
			$errorList = $this->instance->errorInfo();
		}

		return end($errorList);
	}

    /**
     * @return mixed
     */
	public function error_list()
	{
		if (is_object($this->stmt)) {
			$errorList = $this->stmt->errorInfo();
		} else {
			$errorList = $this->instance->errorInfo();
		}

		return $errorList;
	}

	/**
	 * @param $sql
	 * @return mixed
	 */
	public function show_fields($sql)
	{
		return $this->instance->query($sql);
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
     * @return mixed
     */
	public function prepare($sql)
	{
		return $this->instance->prepare($sql);
	}

    /**
     * 预处理
     * @param string $sql
     * @return mixed
     */
    public function prepare_sql($sql)
    {
        return $this->stmt = $this->prepare($sql);
    }

    /**
     * 绑定参数
     * @param string $parameter
     * @param mixed $variable
     * @return mixed
     */
	public function bind_param($parameter, &$variable)
	{
		return call_user_func_array(array($this->stmt, 'bindParam'), func_get_args());
	}

    /**
     * 绑定参数
     * @param $name
     * @param $value
     * @param int $type
     * @return mixed
     */
	public function bind_value($name, $value, $type = PDO::PARAM_STR)
	{
		return $this->stmt->bindValue($name, $value, $type);
	}

    /**
     * 返回绑定参数信息
     * @return false|string
     */
	public function debugDumpParams()
	{
		ob_start();
		$this->stmt->debugDumpParams();
		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}

    /**
     * 获取结果集数据
     * @param int|string $dataType
     * @param bool $queryRow
     * @return array
     */
	public function get_all_result($dataType = DriverBase::DATA_TYPE_ARRAY, $queryRow = false)
	{
		$result = array();

		if (is_object($this->stmt)) {
            if ($dataType == self::DATA_TYPE_OBJECT) {
                while ($row = $this->stmt->fetchObject()) {
                    $result[] = $row;
                    if ($queryRow) break;
                }
            } elseif ($dataType == self::DATA_TYPE_ARRAY) {
                while ($row = $this->stmt->fetch(PDO::FETCH_ASSOC)) {
                    $result[] = $row;
                    if ($queryRow) break;
                }
            } else {
                if (class_exists($dataType)) {
                    while ($row = $this->stmt->fetch(PDO::FETCH_ASSOC)) {
                        $object = new $dataType();
                        foreach ($row as $key => $value) {
                            $object->$key = $value;
                        }
                        $result[] = $object;
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
	public function execute(){
        return $this->stmt->execute();
    }

	/**
	 * @return mixed
	 */
	public function execute_sql()
	{
		return $this->execute();
	}

    /**
     * 找不到方法时
     * @param string $name
     * @param $params
     * @return mixed
     */
	public function __call($name, $params)
	{
		if ($this->instance && method_exists($this->instance, $name)) {
			return call_user_func_array(array($this->instance, $name), $params);
		}
		parent::__call($name, $params);
	}
}

