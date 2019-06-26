<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 数据库接口基类DatabaseBase
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use \Exception;
use Ocara\Core\Sql;
use Ocara\Core\DriverBase;

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

    const EVENT_BEFORE_EXECUTE_SQL = 'beforeExecuteSql';
    const EVENT_AFTER_EXECUTE_SQL = 'afterExecuteSql';

	/**
	 * 连接属性
	 */
	protected $pdoName;
	protected $connectName;
	protected $wakeUpTimes = 0;
	protected $pConnect;

	private $error = array();
	private static $connects = array();

	protected static $paramOptions = array(
		'set', 		'where', 	'groupBy',
		'having', 	'limit', 	'orderBy',
		'more',     'bind'
	);

    /**
     * 初始化方法
     * DatabaseBase constructor.
     * @param array $config
     * @throws \Ocara\Exceptions\Exception
     */
	public function __construct(array $config)
	{
		$options = array(
			'host', 'port', 'type', 'class', 'pconnect',
			'name', 'username', 'prefix', 'charset',
			'timeout', 'socket', 'options', 'keywords', 'isPdo'
		);

		$values = array_fill(0, count($options), OC_EMPTY);
		$config = array_merge(array_combine($options, $values), $config);
		$config['name'] = ocDel($config, 'name');

		if (empty($config['charset'])) {
			$config['charset'] = 'utf8';
		}
		if (empty($config['socket'])) {
			$config['socket'] = null;
		}
		if (empty($config['options'])) {
			$config['options'] = array();
		}

		if (!isset($config['prepare'])) {
			$config['prepare'] = true;
		}
		if (!isset($config['pconnect'])) {
			$config['pconnect'] = false;
		}
		if (!isset($config['isPdo'])) {
			$config['isPdo'] = true;
		}

		if (empty($config['keywords'])) {
			$config['keywords'] = array();
		} else {
			$keywords = explode(',', $config['keywords']);
			$config['keywords'] = array_map(
				'trim', array_map('strtolower', $keywords)
			);
		}

		$this->config = $config;
		ocDel($this->config, 'password');

		$this->init($config);
	}

    /**
     * 注册事件
     * @throws \Ocara\Exceptions\Exception
     */
    public function registerEvents()
    {
        $this->event(self::EVENT_BEFORE_EXECUTE_SQL)
            ->append(ocConfig(array('EVENT', 'database', 'before_execute_sql'), null));

        $this->event(self::EVENT_AFTER_EXECUTE_SQL)
            ->append(ocConfig(array('EVENT', 'database', 'after_execute_sql'), null));
    }

	/**
	 * 设置连接名称
	 * @param $connectName
	 */
	public function setConnectName($connectName)
	{
		$this->connectName = $connectName;
	}

	/**
	 * 获取连接名称
	 * @return mixed
	 */
	public function getConnectName()
	{
		return $this->connectName;
	}

    /**
     * 初始化设置
     * @param array $config
     * @throws \Ocara\Exceptions\Exception
     */
	public function init(array $config)
	{
		$config['password'] = ocGet('password', $config);
		$connectName = $config['connect_name'];
		$this->setConnectName($connectName);

		$exists = isset(self::$connects[$connectName]) && self::$connects[$connectName] instanceof DriverBase;
		if ($exists) {
			$this->setPlugin(self::$connects[$connectName]);
		} else {
			$this->setPlugin($this->getDriver($config));
			self::$connects[$connectName] = $this->plugin();
			$this->isPconnect($config['pconnect']);
			$this->plugin()->connect();
			$this->isPrepare($config['prepare']);
		}

		if (!$exists) {
			$this->setCharset($config['charset']);
		}
	}

    /**
     * 是否PDO连接
     * @return bool
     */
	public function isPdo(){
        return $this->plugin()->driveType() == DriverBase::DRIVE_TYPE_PDO;
    }

    /**
     * 获取数据库驱动类
     * @param array $data
     * @return mixed
     * @throws \Ocara\Exceptions\Exception
     */
	public function getDriver(array $data)
	{
		if ($this->config['isPdo'] && ocCheckExtension($this->pdoName, false)) {
			$object = $this->loadDatabase('Pdo');
			$object->init($this->getPdoParams($data));
		} else {
			$object = $this->loadDatabase($data['class']);
			$object->init($data);
		}

		return $object;
	}

    /**
     * 加载数据库驱动类
     * @param string $class
     * @return mixed
     */
	public function loadDatabase($class)
	{
		$class = $class . 'Driver';
		$classInfo = ServiceBase::classFileExists("Databases/Driver/{$class}.php");

		if ($classInfo) {
			list($path, $namespace) = $classInfo;
			include_once($path);
			$class = $namespace . 'Databases\Driver' . OC_NS_SEP . $class;
			if (class_exists($class, false)) {
				$object = new $class();
				return $object;
			}
		}

		$this->showError('not_exists_database');
	}

    /**
     * 获取配置选项
     * @param string $name
     * @return array|bool|mixed|null
     */
	public function getConfig($name = null)
	{
		if (isset($name)) {
			if (ocEmpty($name)) {
				return null;
			}
			return ocGet((string)$name, $this->config);
		}

		return $this->config;
	}

    /**
     * 执行SQL语句
     * @param array $sqlData
     * @param bool $required
     * @return mixed|void
     * @throws \Ocara\Exceptions\Exception
     */
	public function execute(array $sqlData, $required = true)
	{
	    list($sql, $params) = $sqlData;
		$this->fire(
		    self::EVENT_BEFORE_EXECUTE_SQL,
            array($sql, date(ocConfig(array('DATE_FORMAT', 'datetime'))))
        );

		try {
            $result = null;
			if ($this->prepared && $params) {
				$this->plugin()->prepare_sql($sql);
				$this->bindParams($params);
				$result = $this->plugin()->execute_sql();
			} else {
				$result = $this->plugin()->query_sql($sql);
			}
		} catch (Exception $exception) {
			if (!$this->wakeUpTimes) {
				if ($this->plugin()->is_not_active()) {
					$this->plugin()->wake_up();
				}
				$this->wakeUpTimes++;
				$result = call_user_func_array(array($this, __METHOD__), func_get_arg());
				return $result;
			}
            ocService()->error->show($exception->getMessage());
		}

        $result = $this->checkError($result, array($sql, $params), $required);
		return $result;
	}

    /**
     * 获取查询结果
     * @param bool $queryRow
     * @param bool $count
     * @param array $unions
     * @param string $dataType
     * @return array|mixed
     */
	public function getResult($queryRow = false, $count = false, $unions = array(), $dataType = null)
    {
        $dataType = $dataType ? : DriverBase::DATA_TYPE_ARRAY;

        if ($count) {
            $result = $this->plugin()->get_all_result($dataType, $queryRow);
            $total = 0;
            if (!empty($unions['models'])) {
                foreach ($result as $row) {
                    $num = reset($row);
                    $total += (integer)$num;
                }
            } elseif ($queryRow) {
                $row = reset($result);
                $total = is_array($row) ? $row['total'] : $row->total;
            } else {
                $total = count($result);
            }
            $result = array(array('total' => $total));
        } else {
            $result = $this->plugin()->get_all_result($dataType, $queryRow);
        }

        if ($queryRow && $result && empty($debug)) {
            $result = reset($result);
        }

        return $result;
    }

    /**
     * 查询多行记录
     * @param string|array $sqlData
     * @param bool $debug
     * @param bool $count
     * @param array $unions
     * @param null $dataType
     * @return array|bool|mixed
     * @throws \Ocara\Exceptions\Exception
     */
    public function query($sqlData, $debug = false, $count = false, $unions = array(), $dataType = null)
    {
        $sqlData = $this->formatSqlData($sqlData);
        $result = $this->checkDebug($debug, $sqlData);

        if (!$result) {
            $this->executeQuery($sqlData, $count, $unions);
            $result = $this->getResult(false, $count, $unions, $dataType);
        }

        return $result;
    }

    /**
     * 查询一行
     * @param string|array $sqlData
     * @param bool $debug
     * @param bool $count
     * @param array $unions
     * @param null $dataType
     * @return array|bool|mixed
     * @throws \Ocara\Exceptions\Exception
     */
    public function queryRow($sqlData, $debug = false, $count = false, $unions = array(), $dataType = null)
    {
        $sqlData = $this->formatSqlData($sqlData);
        $result = $this->checkDebug($debug, $sqlData);

        if (!$result) {
            $this->executeQuery($sqlData, $count, $unions);
            $result = $this->getResult(true, $count, $unions, $dataType);
        }

        return $result;
    }

    /**
     * 查询数据结果
     * @param string|array $sqlData
     * @param bool $count
     * @param array $unions
     * @return mixed|void
     * @throws \Ocara\Exceptions\Exception
     */
    protected function executeQuery($sqlData, $count = false, $unions = array())
    {
        list($sql, $params) = $sqlData;

        if (!empty($unions['models'])) {
            $sql = $this->wrapSql($sql);
            foreach ($unions['models'] as $union) {
                if ($count) {
                    $unionData = $union['model']->getTotal(self::DEBUG_RETURN);
                } else {
                    $unionData = $union['model']->getAll(false, false, self::DEBUG_RETURN);
                }
                list($unionSql, $unionParams) = $unionData;
                $sql .= $this->getUnionSql($unionSql, $union['unionAll']);
                $params = array_merge($params, $unionParams);
            }
            if (!$count && !empty($unions['option'])) {
                $orderBy = $unions['option']['order'];
                $limit = $unions['option']['limit'];
                $sql = $this->getSubQuerySql($sql, $orderBy, $limit);
            }
        }

        $result = $this->execute(array($sql, $params));
        return $result;
    }

    /**
     * 格式化SQL
     * @param $sqlData
     * @return array|string
     */
    protected function formatSqlData($sqlData)
    {
        if (is_string($sqlData)) {
            $sqlData = array($sqlData, array());
        }

        return $sqlData;
    }

	/**
	 * 是否长连接
	 * @param bool $pConnect
	 * @return bool
	 */
	public function isPconnect($pConnect = null)
	{
		if (isset($pConnect)) {
			$this->pConnect = $pConnect ? true : false;
			$this->plugin()->is_pconnect($pConnect);
		}
		return $this->pConnect;
	}

	/**
	 * 是否预处理
	 * @param bool $prepare
	 * @return bool
	 */
	public function isPrepare($prepare = null)
	{
		if (isset($prepare)) {
			$this->prepared = $prepare ? true : false;
			$this->plugin()->is_prepare($prepare);
		}
		return $this->prepared;
	}

    /**
     * 获取最后一次插入记录的自增ID
     * @param string $sql
     * @param bool $debug
     * @return bool|mixed
     * @throws \Ocara\Exceptions\Exception
     */
	public function getInsertId($sql = null, $debug = false)
	{
		if (empty($sql)) $sql = $this->getLastIdSql();
		$result = $this->queryRow($sql, $debug);
		return $result ? $result['id'] : false;
	}

    /**
     * 检测表是否存在
     * @param string $table
     * @param bool $required
     * @return bool|mixed|void
     * @throws \Ocara\Exceptions\Exception
     */
	public function tableExists($table, $required = false)
	{
		$table = $this->getTableFullname($table);
		$sqlData = $this->getSelectSql(1, $table, array('limit' => 1));
        $result = $this->execute($sqlData);

		if ($required) {
			return $result;
		} else {
			return $this->errorExists() === false;
		}
	}

    /**
     * 插入记录
     * @param string $table
     * @param array $data
     * @param bool $debug
     * @return array|bool|mixed|void
     * @throws \Ocara\Exceptions\Exception
     */
	public function insert($table, array $data = array(), $debug = false)
	{
		if (empty($data)) {
			$this->showError('fault_save_data');
		}

		$table = $this->getTableFullname($table);
		$sqlData = $this->getInsertSql($table, $data);

        $result = $this->checkDebug($debug, $sqlData);
        if (!$result) {
            $result = $data ? $this->execute($sqlData) : false;
            $result = $result ? $this->getInsertId() : false;
        }

		return $result;
	}

    /**
     * 更新记录
     * @param string $table
     * @param null $data
     * @param null $condition
     * @param bool $debug
     * @return array|bool|mixed|void
     * @throws \Ocara\Exceptions\Exception
     */
	public function update($table, $data = null, $condition = null, $debug = false)
	{
		if (empty($data)) {
			$this->showError('fault_save_data');
		}

		$table = $this->getTableFullname($table);
		$condition = $this->parseCondition($condition);
		$sqlData = $this->getUpdateSql($table, $data, $condition);

        $result = $this->checkDebug($debug, $sqlData);
        if (!$result) {
            $result = $data ? $this->execute($sqlData) : false;
        }

		return $result;
	}

    /**
     * 删除记录
     * @param string $table
     * @param $condition
     * @param bool $debug
     * @return array|bool|mixed|void
     * @throws \Ocara\Exceptions\Exception
     */
	public function delete($table, $condition, $debug = false)
	{
		$table = $this->getTableFullname($table);
		$condition = $this->parseCondition($condition);
		$sqlData = $this->getDeleteSql($table, $condition);

        $result = $this->checkDebug($debug, $sqlData);
        if (!$result) {
            $result = $this->execute($sqlData);
        }

		return $result;
	}

	/**
	 * 获取表全名
	 * @param string $table
	 * @return string
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
			$databaseName = $this->config['name'];
			if ($this->config['prefix']) {
				$table = $this->config['prefix'] . $table;
			}
		}

		return $this->getTableNameSql($databaseName, $table);
	}

    /**
     * 选择数据库
     * @param string $name
     * @return mixed
     */
	public function selectDatabase($name)
	{
		$result = $this->plugin()->selectDatabase($name);

		if ($result) {
			$this->config['name'] = $name;
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
		return $this->config['keywords'] ? $this->config['keywords'] : array();
	}

	/**
	 * 事务开始
	 */
	public function beginTransaction()
	{
		$this->autocommit(false);
		$result = $this->plugin()->begin_transaction();
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
		$result = $this->plugin()->autocommit($autocommit);
		return $result;
	}

	/**
	 * 事务提交
	 */
	public function commit()
	{
		$result = $this->plugin()->commit();
		$this->autocommit(true);
		return $result;
	}

	/**
	 * 事务回滚
	 */
	public function rollback()
	{
		$result = $this->plugin()->rollback();
		$this->autocommit(true);
		return $result;
	}

	/**
	 * 绑定参数
	 * @param string $type
	 * @param array $option
	 * @param scalar $params
	 */
	public function bindParam($option, $type, &$params)
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
		$this->params[$option] = array_merge($this->params[$option], $data);
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
     * 解析参数类型
     * @param mixed $value
     * @return mixed
     */
	private function parseParamType($value)
	{
		$mapTypes = $this->plugin()->get_param_types();

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
	 * 绑定参数
	 * @param array $params
	 */
	protected function bindParams(array $params)
	{
		$types = OC_EMPTY;
		$data = array();
		$paramData = array();
		$bindValues = array();

		foreach ($params as $row) {
			foreach (self::$paramOptions as $option) {
				if ($option == 'bind') {
					if (isset($row[$option])) {
						$bindValues = $row[$option];
					}
				} elseif (!empty($row[$option])) {
					$paramData = array_merge($paramData, $row[$option]);
				}
			}
		}

		foreach ($paramData as $key => &$value) {
			$type = $this->parseParamType($value);
			if ($this->isPdo()) {
				$this->plugin()->bind_param($key + 1, $value, $type);
			} else {
				$types = $types . $type;
				$data[] = &$value;
			}
		}

		if (!$this->isPdo() && $types) {
			array_unshift($data, $types);
			call_user_func_array(array($this->plugin(), 'bind_param'), $data);
		}

		if ($bindValues && method_exists($this->plugin(), 'bind_value')) {
			foreach ($bindValues as $name => $value) {
				$this->plugin()->bind_value($name, $value);
			}
		}
	}

	/**
	 * 保存错误信息
	 */
	public function setError()
	{
		$this->error = array();

		if ($this->plugin()->error_no() > 0) {
			$this->error['errorCode'] = $this->plugin()->error_no();
			$this->error['errorMessage'] = $this->plugin()->error();
			$this->error['errorList'] = $this->plugin()->error_list();
		}
	}

	/**
	 * 获取错误代码
	 */
	public function getErrorCode()
	{
		return ocGet('errorCode', $this->error);
	}

	/**
	 * 获取错误信息
	 */
	public function getError()
	{
		return ocGet('errorMessage', $this->error);
	}

	/**
	 * 获取错误列表
	 */
	public function getErrorList()
	{
		return ocGet('errorList', $this->error);
	}

	/**
	 * 检测是否出错
	 */
	public function errorExists()
	{
		return (boolean)$this->error;
	}

    /**
     * 显示错误信息
     * @param $error
     */
	public function showError($error = null)
	{
        $error = $error ? : $this->getError();
        ocService()->error->show($error);
	}

    /**
     * 检测错误
     * @param $ret
     * @param $sqlData
     * @param bool $required
     * @throws \Ocara\Exceptions\Exception
     */
	public function checkError($ret, $sqlData, $required = true)
	{
		$this->setError();
		$errorExists = $this->errorExists();
		$error = $errorExists ? $this->getError() : null;

		if ($sqlData) {
		    $dateFormat = ocConfig('DATE_FORMAT.datetime');
			$params = array($sqlData, $errorExists, $error, $ret, date($dateFormat));
			$this->fire(self::EVENT_AFTER_EXECUTE_SQL, $params);
		}

		if ($required && $errorExists) {
			return $this->showError($error);
		}

		return $ret;
	}

	/**
	 * debug参数检查
	 * @param bool $debug
	 * @param array $sqlData
	 * @return array|bool
	 */
	private function checkDebug($debug, $sqlData)
	{
		if ($debug) {
			if ($debug === self::DEBUG_RETURN) {
				return $sqlData;
			}
			if ($debug === self::DEBUG_PRINT || $debug === self::DEBUG_DUMP) {
				if (OC_SYS_MODEL == 'develop') {
					if ($debug === self::DEBUG_DUMP) {
						ocDump($sqlData);
					} else {
						ocPrint($sqlData);
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