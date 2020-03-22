<?php
/**
 * 数据库基类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use Ocara\Exceptions\Exception;
use Ocara\Sql\Generator;

defined('OC_PATH') or exit('Forbidden!');

class DatabaseBase extends Base
{
    private $error = array();

    private static $connects = array();

    protected $config;
    protected $pdoName;
    protected $connectName;
    protected $wakeUpTimes = 0;
    protected $pConnect;
    protected $lastSql;
    protected $prepared;
    protected $params;
    protected $selectedDatabase;
    protected $keywords = array();

    protected static $paramOptions = array(
        'set', 'where', 'groupBy',
        'having', 'limit', 'orderBy',
        'more', 'bind'
    );

    const EVENT_BEFORE_EXECUTE_SQL = 'beforeExecuteSql';
    const EVENT_AFTER_EXECUTE_SQL = 'afterExecuteSql';
    const EVENT_BEFORE_SHOW_ERROR = 'beforeShowError';

    /**
     * 初始化方法
     * DatabaseBase constructor.
     * @param array $config
     * @throws Exception
     */
    public function __construct(array $config)
    {
        $this->init($this->initConfig($config));
    }

    /**
     * 初始化配置
     * @param $config
     * @return array
     */
    public function initConfig($config)
    {
        $options = array(
            'host', 'port', 'type', 'class',
            'name', 'username', 'prefix', 'charset',
            'timeout', 'socket', 'options', 'keywords',
            'is_filter_keywords'
        );

        $values = array_fill_keys($options, OC_EMPTY);
        $config = array_merge(array_combine($options, $values), $config);

        if (!$config['charset']) {
            $config['charset'] = 'utf8';
        }
        if (!$config['socket']) {
            $config['socket'] = null;
        }
        if (!$config['options']) {
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

        if (isset($config['is_filter_keywords']) && $config['is_filter_keywords'] === false) {
            if (empty($config['keywords'])) {
                $config['keywords'] = $this->keywords;
            } else {
                $keywords = is_array($config['keywords']) ? $config['keywords'] : array();
                $config['keywords'] = array_map('trim', $keywords);
            }
        } else {
            $config['keywords'] = array();
        }

        $this->config = $config;
        ocDel($this->config, 'password');

        return $config;
    }

    /**
     * 注册事件
     * @throws Exception
     */
    public function registerEvents()
    {
        $this->event(self::EVENT_BEFORE_EXECUTE_SQL)
            ->appendAll(ocConfig(array('EVENTS', 'database', 'before_execute_sql'), array()));

        $this->event(self::EVENT_AFTER_EXECUTE_SQL)
            ->appendAll(ocConfig(array('EVENTS', 'database', 'after_execute_sql'), array()));

        $this->event(self::EVENT_BEFORE_SHOW_ERROR)
            ->setDefault(array($this, 'beforeShowError'));
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
     * @throws Exception
     */
    public function init(array $config)
    {
        $config['password'] = isset($config['password']) ? $config['password'] : null;
        $connectName = $config['connect_name'];
        $this->setConnectName($connectName);

        if (isset(self::$connects[$connectName]) && self::$connects[$connectName] instanceof DriverBase) {
            $this->setPlugin(self::$connects[$connectName]);
        } else {
            $plugin = $this->setPlugin($this->getDriver($config));
            self::$connects[$connectName] = $plugin;
            $this->isPconnect($config['pconnect']);
            $plugin->connect();
            $this->isPrepare($config['prepare']);
            $this->setCharset($config['charset']);
        }
    }

    /**
     * 获取设置字符集数据
     * @param $charset
     * @return mixed|void|null
     * @throws Exception
     */
    public function setCharset($charset)
    {
        $generator = new Generator($this);
        $sql = $generator->getSetCharsetSql($charset);
        $sqlData = $generator->getSqlData($sql);
        $result = $this->execute($sqlData);
        return $result;
    }

    /**
     * 是否PDO连接
     * @return bool
     * @throws Exception
     */
    public function isPdo()
    {
        return $this->plugin()->driveType() == DriverBase::DRIVE_TYPE_PDO;
    }

    /**
     * 获取数据库驱动类
     * @param array $data
     * @return mixed
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
            if (class_exists($class)) {
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
            $name = (string)$name;
            return isset($this->config[$name]) ? $this->config[$name] : null;
        }

        return $this->config;
    }

    /**
     * 获取数据库类型
     * @return array|bool|mixed|null
     */
    public function getType()
    {
        return $this->getConfig('type');
    }

    /**
     * 执行SQL语句
     * @param array $sqlData
     * @param bool $required
     * @return mixed|void|null
     * @throws Exception
     */
    public function execute(array $sqlData, $required = true)
    {
        $plugin = $this->plugin();
        list($sql, $params) = $sqlData;

        $this->fire(
            self::EVENT_BEFORE_EXECUTE_SQL,
            array($sql, date(ocConfig(array('DATE_FORMAT', 'datetime'))))
        );

        try {
            $result = null;
            if ($this->prepared && $params) {
                $plugin->prepare_sql($sql);
                $this->bindParams($params);
                $result = $plugin->execute_sql();
            } else {
                $result = $plugin->query_sql($sql);
            }
        } catch (\Exception $exception) {
            if (!$this->wakeUpTimes) {
                if ($plugin->is_not_active()) {
                    $plugin->wake_up();
                }
                $this->wakeUpTimes++;
                $result = call_user_func_array(array($this, __METHOD__), func_get_arg());
                return $result;
            }
            ocService()->error->show($exception->getMessage());
        }

        $this->lastSql = $sqlData;
        $result = $this->checkError($result, array($sql, $params), $required);

        return $result;
    }

    /**
     * 获取查询结果
     * @param bool $queryRow
     * @param bool $count
     * @param bool $isUnion
     * @param int|string $dataType
     * @param array $shardingCurrent
     * @return array|mixed|null
     * @throws Exception
     */
    public function getResult($queryRow = false, $count = false, $isUnion = false, $dataType = null, $shardingCurrent = array())
    {
        $dataType = $dataType ?: DriverBase::DATA_TYPE_ARRAY;
        $plugin = $this->plugin();

        if ($count) {
            $result = $plugin->get_all_result($dataType, $queryRow);
            $total = 0;
            if ($isUnion) {
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
            $result = $plugin->get_all_result($dataType, $queryRow, $shardingCurrent);
        }

        if ($queryRow) {
            if ($result) {
                $result = reset($result);
            } else {
                $result = null;
                if ($dataType == DriverBase::DATA_TYPE_ARRAY) {
                    $result = array();
                }
            }
        }

        return $result;
    }

    /**
     * 查询多行记录
     * @param $sqlData
     * @param bool $count
     * @param bool $isUnion
     * @param int|string $dataType
     * @param array $shardingCurrent
     * @return array|mixed|void|null
     * @throws Exception
     */
    public function query($sqlData, $count = false, $isUnion = false, $dataType = null, $shardingCurrent = array())
    {
        $sqlData = $this->formatSqlData($sqlData);
        $result = $this->execute($sqlData);

        if ($result !== false) {
            $result = $this->getResult(false, $count, $isUnion, $dataType, $shardingCurrent);
        }

        return $result;
    }

    /**
     * 查询一行
     * @param $sqlData
     * @param bool $count
     * @param bool $isUnion
     * @param int|string $dataType
     * @param array $shardingCurrent
     * @return array|mixed|void|null
     * @throws Exception
     */
    public function queryRow($sqlData, $count = false, $isUnion = false, $dataType = null, $shardingCurrent = array())
    {
        $sqlData = $this->formatSqlData($sqlData);
        $result = $this->execute($sqlData);

        if ($result !== false) {
            $result = $this->getResult(true, $count, $isUnion, $dataType, $shardingCurrent);
        }

        return $result;
    }

    /**
     * 获取最后执行的SQL
     * @return mixed
     */
    public function getLastSql()
    {
        return $this->lastSql;
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
     * @param null $pConnect
     * @return bool
     * @throws Exception
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
     * @throws Exception
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
     * 选择数据库
     * @param null $name
     */
    public function baseSelectDatabase($name = null)
    {
    }

    /**
     * 选择数据库
     * @param string $name
     * @return mixed
     */
    public function selectDatabase($name = null)
    {
        $name = $name ?: $this->config['name'];
        $result = $this->baseSelectDatabase($name);

        if ($result) {
            $this->selectedDatabase = $name;
        } else {
            $this->showError('failed_select_database');
        }

        return $result;
    }

    /**
     * 是否已选择数据库
     * @return bool
     */
    public function isSelectedDatabase()
    {
        return !!$this->selectedDatabase;
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
        $plugin = $this->plugin();
        $this->autocommit(false);

        $result = $plugin->begin_transaction();
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
     * @param $option
     * @param $type
     * @param $params
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

        if (is_int($value)) {
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
     * @throws Exception
     */
    protected function bindParams(array $params)
    {
        $types = OC_EMPTY;
        $data = array();
        $paramData = array();
        $bindValues = array();
        $plugin = $this->plugin();

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

        foreach ($paramData as $key => $value) {
            $type = $this->parseParamType($value);
            if ($this->isPdo()) {
                $plugin->bind_param($key + 1, $paramData[$key], $type);
            } else {
                $types = $types . $type;
                $data[] = &$paramData[$key];
            }
        }

        if (!$this->isPdo() && $types) {
            array_unshift($data, $types);
            call_user_func_array(array($plugin, 'bind_param'), $data);
        }

        if ($bindValues && method_exists($plugin, 'bind_value')) {
            foreach ($bindValues as $name => $value) {
                $plugin->bind_value($name, $value);
            }
        }
    }

    /**
     * 格式化字段值为适合的数据类型
     * @param $fields
     * @param array $data
     * @param bool $isCondition
     * @return array
     */
    public function formatFieldValues($fields, $data = array(), $isCondition = false)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($value && $isCondition) {
                    if (count($value) == 1) { //只有一个元素，直接变成字符
                        $value = $value[0];
                        $value = $this->formatOneFieldValue($fields, $key, $value);
                    } else {
                        $newValue = $value[1];
                        if (!is_array($newValue)) { //数组不处理
                            $value[1] = $this->formatOneFieldValue($fields, $key, $newValue);
                        }
                    }
                }
            } else {
                $value = $this->formatOneFieldValue($fields, $key, $value);
            }
            $data[$key] = $value;
        }
        return $data;
    }

    /**
     * 保存错误信息
     */
    public function setError()
    {
        $this->error = array();
        $plugin = $this->plugin();

        if ($plugin->error_exists()) {
            $this->error['errorCode'] = $plugin->error_no();
            $this->error['errorMessage'] = $plugin->error();
            $this->error['errorList'] = $plugin->error_list();
        }
    }

    /**
     * 获取错误代码
     */
    public function getErrorCode()
    {
        return isset($this->error['errorCode']) ? $this->error['errorCode'] : null;
    }

    /**
     * 获取错误信息
     */
    public function getError()
    {
        return isset($this->error['errorMessage']) ? $this->error['errorMessage'] : null;
    }

    /**
     * 获取错误列表
     */
    public function getErrorList()
    {
        return !empty($this->error['errorList']) ? $this->error['errorList'] : array();
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
     * @param string $error
     * @param array $params
     * @throws Exception
     */
    public function showError($error = null, $params = array())
    {
        $error = $error ?: $this->getError();
        $this->fire(self::EVENT_BEFORE_SHOW_ERROR, array($error, $params));
        ocService()->error->show($error);
    }

    /**
     * 显示错误前置事件
     * @param $error
     * @param $params
     * @throws Exception
     */
    public function beforeShowError($error, $params)
    {
        ocService()->log->error($error . '|' . ocJsonEncode($params));
    }

    /**
     * 检测错误
     * @param $result
     * @param $sqlData
     * @param bool $required
     * @throws Exception
     */
    public function checkError($result, $sqlData, $required = true)
    {
        $this->setError();
        $errorExists = $this->errorExists();
        $error = $errorExists ? $this->getError() : null;
        $params = array();

        if ($sqlData) {
            $datetime = date(ocConfig('DATE_FORMAT.datetime'));
            $params = compact('sqlData', 'errorExists', 'error', 'result', 'datetime');
            $this->fire(self::EVENT_AFTER_EXECUTE_SQL, array($params));
        }

        if ($required && $errorExists) {
            return $this->showError($error, $params);
        }

        return $result;
    }
}