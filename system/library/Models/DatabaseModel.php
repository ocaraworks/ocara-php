<?php
/**
 * 数据库模型基类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Models;

use Ocara\Core\Event;
use \ReflectionObject;
use Ocara\Core\DriverBase;
use Ocara\Sql\Generator;
use Ocara\Exceptions\Exception;
use Ocara\Core\DatabaseBase;
use Ocara\Core\ModelBase;
use Ocara\Iterators\Database\BatchQueryRecords;
use Ocara\Iterators\Database\EachQueryRecords;

abstract class DatabaseModel extends ModelBase
{

    /**
     * @var @primary 主键字段列表
     * @var $primaries 主键字段数组
     */
    protected static $primary;
    protected static $table;
    protected static $entity;
    protected static $database;

    protected $alias;
    protected $module;
    protected $serverName = 'defaults';

    protected $tag;
    protected $databaseName;
    protected $tableName;
    protected $autoIncrementField;
    protected $isClear = true;

    protected $primaries = array();
    protected $sql = array();
    protected $fields = array();
    protected $lastSql = array();

    protected static $config = array();
    protected static $configPath = array();

    protected static $optionMethods = array(
        'orderBy', 'groupBy', 'limit', 'having', 'more'
    );

    const EVENT_BEFORE_CONNECT = 'beforeConnect';
    const EVENT_AFTER_CONNECT = 'afterConnect';
    const EVENT_BEFORE_QUERY = 'beforeQuery';
    const EVENT_AFTER_QUERY = 'afterQuery';

    const EVENT_BEFORE_SELECT_QUERY = 'beforeSelectQuery';
    const EVENT_AFTER_SELECT_QUERY = 'afterSelectQuery';
    const EVENT_GET_FIELDS_CACHE = 'onGetCacheFields';
    const EVENT_SAVE_FIELDS_CACHE = 'onSaveCacheFields';

    /**
     * 初始化
     * DatabaseModel constructor.
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
        if (!static::$primary) {
            ocService()->error->show('no_primaries');
        }

        $this->tag = self::getClass();
        $this->tableName = static::getDefaultTableName();
        $this->databaseName = static::$database ?: null;
        $this->primaries = static::getPrimaries();

        if (method_exists($this, '__start')) $this->__start();
        if (method_exists($this, '__model')) $this->__model();

        return $this;
    }

    /**
     * 注册事件
     */
    public function registerEvents()
    {
        $this->bindEventHandler($this);
    }

    /**
     * 获取Model标记
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * 获取表名
     * @return mixed
     * @throws Exception
     */
    public static function getDefaultTableName()
    {
        if (empty(static::$table)) {
            ocService()->error->show('no_table');
        }
        return static::$table;
    }

    /**
     * 获取表名
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * 获取表的全名（包括前缀）
     * @return string
     * @throws Exception
     */
    public function getTableFullname()
    {
        return $this->connect()->getTableFullname($this->tableName);
    }

    /**
     * 获取当前服务器
     * @return string
     */
    public function getConnectName()
    {
        return $this->serverName;
    }

    /**
     * 获取当前数据库
     * @return string
     */
    public static function getDatabase()
    {
        return static::$database;
    }

    /**
     * 获取当前数据库名称
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->databaseName;
    }

    /**
     * 获取主键
     * @return array
     */
    public static function getPrimaries()
    {
        return static::$primary ? explode(',', static::$primary) : array();
    }

    /**
     * 执行分库分表
     * @param array $data
     * @param string $relationModel
     * @return $this
     */
    public function sharding(array $data = array(), $relationModel = null)
    {
        if (empty($this->sql['sharding'])) {
            $this->sql['sharding'] = array();
        }

        if (func_num_args() >= 2) {
            if ($relationModel) {
                $relateData = $this->getSharding('relate');
                $this->sql['sharding']['relate'] = array_merge($relateData, array($relationModel => $data));
            }
        } else {
            $currentData = $this->getSharding();
            $this->sql['sharding']['current'] = array_merge($currentData, $data);
            if ($this->sql['sharding']['current'] && method_exists($this, '__sharding')) {
                $this->__sharding($this->sql['sharding']['current']);
            }
        }

        return $this;
    }

    /**
     * 获取分表信息
     * @param string $type
     * @return array|bool|mixed|null
     */
    public function getSharding($type = 'current')
    {
        $type = $type ?: 'current';
        return !empty($this->sql['sharding'][$type]) ? $this->sql['sharding'][$type] : array();
    }

    /**
     * 加载配置文件
     */
    public static function loadConfig()
    {
        $class = self::getClass();

        if (empty(self::$config[$class])) {
            $model = new static();
            self::$config[$class] = $model->getModelConfig();
        }
    }

    /**
     * 获取Model的配置
     * @return array
     * @throws Exception
     */
    public function getModelConfig()
    {
        $paths = $this->getConfigPath();

        $modelConfig = array(
            'MAPS' => $this->fieldsMap() ?: array(),
            'RELATIONS' => $this->relations() ?: array(),
            'RULES' => $this->rules() ?: array(),
            'LANG' => array()
        );

        if (ocFileExists($paths['lang'])) {
            $lang = @include($paths['lang']);
            if ($lang && is_array($lang)) {
                $modelConfig['LANG'] = array_merge($modelConfig['LANG'], $lang);
            }
        }

        if ($paths['moduleLang'] && ocFileExists($paths['moduleLang'])) {
            $lang = @include($paths['moduleLang']);
            if ($lang && is_array($lang)) {
                $modelConfig['LANG'] = array_merge($modelConfig['LANG'], $lang);
            }
        }

        ksort($modelConfig);
        return $modelConfig;
    }

    /**
     * 数据表字段别名映射
     * @return array
     */
    public function fieldsMap()
    {
        return array();
    }

    /**
     * 数据表关联
     * @return array
     */
    public function relations()
    {
        return array();
    }

    /**
     * 字段验证配置
     * @return array
     */
    public function rules()
    {
        return array();
    }

    /**
     * 获取配置数据
     * @param string $key
     * @param string $field
     * @return array|bool|mixed|null
     */
    public static function getConfig($key = null, $field = null)
    {
        self::loadConfig();
        $tag = self::getClass();
        $length = func_num_args();

        if ($length) {
            $key = strtoupper($key);
            if ($length >= 2) {
                return isset(self::$config[$tag][$key][$field]) ? self::$config[$tag][$key][$field] : null;
            }
            return isset(self::$config[$tag][$key]) ? self::$config[$tag][$key] : array();
        }

        return self::$config[$tag];
    }

    /**
     * 获取配置文件路径
     * @param string $modulePath
     * @return array|mixed
     * @throws Exception
     */
    public function getConfigPath($modulePath = null)
    {
        $tag = $this->tag;

        if (!empty(self::$configPath[$tag])) {
            return self::$configPath[$tag];
        }

        $modelSuffix = ocConfig('MODEL_SUFFIX');
        $moduleLang = OC_EMPTY;
        $language = ocService()->app->getLanguage();

        try {
            $ref = new ReflectionObject($this);
            $filePath = ocCommPath($ref->getFileName());
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }

        $file = lcfirst(substr(basename($filePath), 0, -(strlen($modelSuffix) + 4))) . '.php';
        $dir = dirname($filePath) . OC_DIR_SEP;

        if ($this->module) {
            list($rootPath, $subDir) = ocSeparateDir($dir, '/model/database/');
            $modulePath = $modulePath ?: (OC_MODULE_PATH ?: ocPath('modules'));
            $moduleLang = ocDir($modulePath, $this->module . '/lang/' . $language . '/database/', $subDir) . $file;
        } else {
            list($rootPath, $subDir) = ocSeparateDir($dir, '/application/model/database/');
        }

        $subDir = rtrim($subDir, '/');
        $paths = array(
            'lang' => ocPath('lang', ocDir($language, 'database', $subDir) . $file),
            'fields' => ocPath('fields', ocDir($subDir) . $file),
            'moduleLang' => $moduleLang
        );

        return self::$configPath[$tag] = $paths;
    }

    /**
     * 数据字段别名映射
     * @param array $data
     * @return array
     */
    public static function mapData(array $data)
    {
        $config = self::getConfig('MAPS');
        if (!$config) return $data;

        $result = array();
        foreach ($data as $key => $value) {
            if (isset($config[$key])) {
                $result[$config[$key]] = $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * 字段别名映射
     * @param $field
     * @return string
     */
    public static function mapField($field)
    {
        $config = self::getConfig('MAPS');
        $result = isset($config[$field]) ? $config[$field] : $field;
        return $result;
    }

    /**
     * 获取当前数据库对象
     * @param bool $master
     * @return mixed|null
     * @throws Exception
     */
    public function db($master = true)
    {
        $connect = $this->connect($master);

        if (!$connect) {
            ocService()->error->show('null_database');
        }

        return $connect;
    }

    /**
     * 切换数据库
     * @param string $name
     */
    public function setDatabase($name)
    {
        $this->databaseName = $name;
    }

    /**
     * 切换数据表
     * @param string $name
     * @param string|array $primary
     */
    public function setTable($name, $primary = null)
    {
        $this->tableName = $name;
        if ($primary) {
            if (is_array($primary)) {
                $this->primaries = $primary;
            } else {
                $this->primaries = explode(',', $primary);
            }
        }
    }

    /**
     * 从数据库获取数据表的字段
     * @param bool $cache
     * @return $this
     * @throws Exception
     */
    public function loadFields($cache = true)
    {
        $fieldsInfo = array();

        if ($cache) {
            if (!$this->fields) {
                $fieldsInfo = $this->fire(self::EVENT_GET_FIELDS_CACHE);
            }
        }

        if (!$fieldsInfo) {
            $plugin = $this->connect();
            $generator = new Generator($plugin);
            $sqlData = $generator->getShowFieldsSql($this->tableName, $this->databaseName);
            $fieldsInfo = $plugin->getFieldsInfo($sqlData);
            $this->fire(self::EVENT_SAVE_FIELDS_CACHE, array($fieldsInfo));
        }

        if ($fieldsInfo) {
            $this->autoIncrementField = !empty($fieldsInfo['autoIncrementField']) ? $fieldsInfo['autoIncrementField'] : OC_EMPTY;
            $this->fields = !empty($fieldsInfo['list']) ? $fieldsInfo['list'] : array();
        }

        return $this;
    }

    /**
     * 获取字段缓存事件处理
     * @param Event $event
     * @param object $eventTarget
     * @return array|mixed
     * @throws Exception
     */
    public function onGetCacheFields($event, $eventTarget)
    {
        return $this->getFieldsConfig();
    }

    /**
     * 保存字段缓存事件处理
     * @param array $fieldsConfig
     * @param Event $event
     * @param object $eventTarget
     * @throws Exception
     */
    public function onSaveCacheFields($fieldsConfig, $event, $eventTarget)
    {
        $paths = $this->getConfigPath();
        $fileCache = ocService()->fileCache;

        $fileCache->format();
        $fileCache->setData($fieldsConfig, null, ltrim(self::getClass(), OC_NS_SEP) . ' Fields');
        $fileCache->save($paths['fields']);
    }

    /**
     * 获取字段配置
     * @return array|mixed
     * @throws Exception
     */
    public function getFieldsConfig()
    {
        if ($this->fields) {
            return array(
                'autoIncrementField' => $this->autoIncrementField,
                'list' => $this->fields,
            );
        } else {
            $paths = $this->getConfigPath();
            $path = ocLowerFile($paths['fields']);
            if (ocFileExists($path)) {
                return @include($path);
            }
        }

        return array();
    }

    /**
     * 获取字段
     * @return array
     * @throws Exception
     */
    public function getFieldsInfo()
    {
        if (empty($this->fields)) {
            $this->loadFields();
        }

        return $this->fields;
    }

    /**
     * 获取字段
     * @return array
     * @throws Exception
     */
    public function getFields()
    {
        return array_keys($this->getFieldsInfo());
    }

    /**
     * 是否有字段
     * @param $field
     * @return bool
     * @throws Exception
     */
    public function hasField($field)
    {
        return in_array($field, $this->getFields());
    }

    /**
     * 获取自增字段名
     * @return mixed
     */
    public function getAutoIncrementField()
    {
        return $this->autoIncrementField;
    }

    /**
     * 清理SQL
     * @param bool $isClear
     * @return $this
     */
    public function clearSql($isClear = true)
    {
        if (func_num_args()) {
            $this->isClear = $isClear ? true : false;
        } else {
            if ($this->isClear) {
                $this->sql = array();
            }
        }
        return $this;
    }

    /**
     * 缓存查询的数据
     * @param string $server
     * @param bool $required
     * @return $this
     * @throws Exception
     */
    public function cache($server = null, $required = false)
    {
        $server = $server ?: ocService()->databases->getDefaultServer();
        $this->sql['cache'] = array($server, $required);
        return $this;
    }

    /**
     * 规定使用主库查询
     * @return $this
     */
    public function master()
    {
        $this->sql['option']['master'] = true;
        return $this;
    }

    /**
     * 规定使用从库查询
     * @return $this
     */
    public function slave()
    {
        $this->sql['option']['master'] = false;
        return $this;
    }

    /**
     * 是否过滤数据
     * @param bool $isFilterData
     * @return $this|mixed|null
     * @throws Exception
     */
    public function filterData($isFilterData = null)
    {
        if (isset($isFilterData)) {
            $this->sql['option']['isFilterData'] = is_bool($isFilterData) ? $isFilterData : false;
            return $this;
        }

        $result = ocConfig('DATABASE_MODEL.auto_filter_data', true);

        if (isset($this->sql['option']['isFilterData'])) {
            $result = $this->sql['option']['isFilterData'];
        }

        return $result;
    }

    /**
     * 是否过滤数据
     * @param bool $isFilterCondition
     * @return $this|mixed|null
     * @throws Exception
     */
    public function filterCondition($isFilterCondition = null)
    {
        if (isset($isFilterCondition)) {
            $this->sql['option']['isFilterCondition'] = is_bool($isFilterCondition) ? $isFilterCondition : false;
            return $this;
        }

        $result = ocConfig('DATABASE_MODEL.auto_filter_condition', true);

        if (isset($this->sql['option']['isFilterCondition'])) {
            $result = $this->sql['option']['isFilterCondition'];
        }

        return $result;
    }

    /**
     * 保存记录
     * @param array $data
     * @param bool $isUpdate
     * @param string $conditionSql
     * @param bool $requireCondition
     * @return array|bool|int|mixed
     * @throws Exception
     */
    public function baseSave($data, $isUpdate = false, $conditionSql = null, $requireCondition = true)
    {
        $plugin = $this->connect();

        if (empty($data)) {
            ocService()->error->show('fault_save_data');
        }

        $this->getFieldsInfo();
        $generator = $this->getSqlGenerator($plugin);
        $isFilterData = $this->filterData();
        $isFilterCondition = $this->filterCondition();

        if ($isUpdate) {
            $conditionSql = $conditionSql ?: $generator->genWhereSql($isFilterCondition);
            if ($requireCondition && !$conditionSql) {
                ocService()->error->show('need_condition');
            }
            $sqlData = $generator->getUpdateSql($this->tableName, $data, $conditionSql, $isFilterData);
        } else {
            $autoIncrementField = $this->getAutoIncrementField();
            if (!in_array($autoIncrementField, $this->primaries)) {
                if (array_diff_key($this->primaries, array_keys($data))) {
                    ocService()->error->show('need_create_primary_data');
                }
            }
            $sqlData = $generator->getInsertSql($this->tableName, $data, $isFilterData);
        }

        $this->lastSql = array($sqlData);

        if ($this->isDebug()) {
            $this->debug(false);
            return false;
        }

        $this->pushTransaction($plugin);
        $result = $data ? $plugin->execute($sqlData) : false;

        if (!$isUpdate) {
            $result = $result ? $this->getInsertId(null, false) : false;
            $lastSql = $this->getLastSql();
            $this->lastSql = array($sqlData, reset($lastSql));
        }

        $this->clearSql();
        return $result;
    }

    /**
     * 获取最后一次插入记录的自增ID
     * @param string $sql
     * @return bool|int|mixed
     * @throws Exception
     */
    public function getInsertId($sql = null)
    {
        $plugin = $this->connect();

        if (empty($sql)) {
            $generator = $this->getSqlGenerator($plugin);
            $sqlData = $generator->getLastIdSql();
        } else {
            $sqlData = array($sql, array());
        }

        $this->lastSql = array($sqlData);

        if ($this->isDebug()) {
            $this->debug(false);
            return false;
        }

        $result = $sqlData ? $plugin->queryRow($sqlData) : 0;
        return $result ? $result['id'] : 0;
    }

    /**
     * 检测表是否存在
     * @param string $table
     * @param bool $required
     * @return bool
     * @throws Exception
     */
    public function tableExists($table, $required = false)
    {
        $plugin = $this->connect();
        $generator = $this->getSqlGenerator($plugin);
        $table = $generator->getTableFullname($table);
        $sqlData = $generator->getSelectSql(1, $table, array('limit' => 1));
        $this->lastSql = array($sqlData);

        if ($this->isDebug()) {
            $this->debug(false);
            return false;
        }

        $result = $plugin->execute($sqlData);

        if ($required) {
            return $result;
        } else {
            return $plugin->errorExists() === false;
        }
    }

    /**
     * 预处理
     * @param bool $prepare
     * @throws Exception
     */
    public function prepare($prepare = true)
    {
        $this->plugin()->is_prepare($prepare);
    }

    /**
     * 推入事务池中
     * @param DatabaseBase|null $plugin
     * @throws Exception
     */
    public function pushTransaction(DatabaseBase $plugin = null)
    {
        if (!$plugin) {
            $plugin = $this->connect();
        }
        ocService()->transaction->push($plugin);
    }

    /**
     * 新建记录
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public function create(array $data)
    {
        $entityClass = $this->getEntityClass();
        $entity = new $entityClass();
        $result = $entity->create($data);
        return $result;
    }

    /**
     * 批量更新记录
     * @param array $data
     * @param int $batchLimit
     * @return array|bool|int|mixed
     * @throws Exception
     */
    public function update(array $data, $batchLimit = 1000)
    {
        $batchLimit = $batchLimit ?: 1000;

        if ($batchLimit) {
            $dataType = $this->getDataType();
            if (!$dataType || in_array($dataType, DriverBase::base_diver_types())) {
                $this->asEntity();
            }

            foreach ($batchObject = $this->batch($batchLimit) as $entityList) {
                foreach ($entityList as $entity) {
                    $entity->data($data);
                    $entity->update();
                }
            }
            $this->lastSql = array($batchObject->getLastSql());
        } else {
            return $this->baseSave($data, true);
        }
    }

    /**
     * 批量删除记录
     * @param int $batchLimit
     * @return bool
     * @throws Exception
     */
    public function delete($batchLimit = 1000)
    {
        $batchLimit = $batchLimit ?: 1000;

        if ($batchLimit) {
            $dataType = $this->getDataType();
            if (!$dataType || in_array($dataType, DriverBase::base_diver_types())) {
                $this->asEntity();
            }

            foreach ($batchObject = $this->batch($batchLimit) as $entityList) {
                foreach ($entityList as $entity) {
                    $entity->delete();
                }
            }
            $this->lastSql = array($batchObject->getLastSql());
        } else {
            return $this->baseDelete();
        }
    }

    /**删除记录
     * @param string $conditionSql
     * @param bool $requireCondition
     * @return bool
     * @throws Exception
     */
    public function baseDelete($conditionSql = null, $requireCondition = true)
    {
        $isFilterCondition = $this->filterCondition();
        $plugin = $this->connect();
        $this->getFieldsInfo();
        $generator = $this->getSqlGenerator($plugin);

        if (!$conditionSql) {
            $conditionSql = $generator->genWhereSql($isFilterCondition);
        }

        if ($requireCondition && !$conditionSql) {
            ocService()->error->show('need_condition');
        }

        $this->pushTransaction($plugin);
        $sqlData = $generator->getDeleteSql($this->tableName, $conditionSql);
        $this->lastSql = array($sqlData);

        if ($this->isDebug()) {
            $this->debug(false);
            return false;
        }

        $result = $plugin->execute($sqlData);
        $this->clearSql();
        return $result;
    }

    /**
     * 直接执行查询语句
     * @param string $sql
     * @return bool
     * @throws Exception
     */
    public function query($sql)
    {
        $plugin = $this->connect();

        if ($sql) {
            $this->fire(self::EVENT_BEFORE_QUERY, array($sql));
            $sqlData = $this->getSqlData($plugin, $sql);
            $this->lastSql = array($sqlData);
            if ($this->isDebug()) {
                $this->debug(false);
                $result = false;
            } else {
                $result = $plugin->query($sqlData);
            }
            $this->fire(self::EVENT_AFTER_QUERY, array($result, $sql));
            return $result;
        }

        return false;
    }

    /**
     * 直接执行查询语句查询一行
     * @param string $sql
     * @return bool
     * @throws Exception
     */
    public function queryRow($sql)
    {
        $plugin = $this->connect();

        if ($sql) {
            $sqlData = $this->getSqlData($plugin, $sql);
            $this->lastSql = array($sqlData);
            if ($this->isDebug()) {
                $this->debug(false);
            } else {
                return $plugin->queryRow($sqlData);
            }
        }

        return false;
    }

    /**
     * 获取SQL生成数据
     * @param DatabaseBase $database
     * @param string $sql
     * @return array
     */
    protected function getSqlData(DatabaseBase $database, $sql)
    {
        if (is_array($sql)) {
            $sqlData = $sql;
        } else {
            $generator = new Generator($database);
            $sqlData = $generator->getSqlData($sql);
        }
        return $sqlData;
    }

    /**
     * 获取SQL
     * @return array
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * 设置SQL
     * @param $sql
     * @return $this
     */
    public function setSql($sql)
    {
        $this->sql = $sql;
        return $this;
    }

    /**
     * 默认查询字段列表
     * @return $this
     */
    public function defaultFields()
    {
        if (method_exists($this, '__fields')) {
            $fields = $this->__fields();
            if ($fields) {
                $this->fields($fields);
            }
        }
        return $this;
    }

    /**
     * 默认查询条件
     * @return $this
     */
    public function defaultCondition()
    {
        if (method_exists($this, '__condition')) {
            $where = $this->__condition();
            if ($where) {
                $this->where($where);
            }
        }
        return $this;
    }

    /**
     * 选择一行
     * @param string|array $condition
     * @param string|array $options
     * @return array|bool
     * @throws Exception
     */
    public function selectOne($condition = null, $options = null)
    {
        $result = $this
            ->asEntity()
            ->baseFind($condition, $options, true);
        return $result;
    }

    /**
     * 选择多条记录
     * @param string|array $condition
     * @param string|array $options
     * @return array|bool
     * @throws Exception
     */
    public function selectAll($condition = null, $options = null)
    {
        $records = $this
            ->asEntity()
            ->baseFind($condition, $options, false);
        return $records;
    }

    /**
     * 返回对象
     * @return $this
     */
    public function asArray()
    {
        return $this->setDataType(DriverBase::DATA_TYPE_ARRAY);
    }

    /**
     * 返回对象
     * @return $this
     */
    public function asObject()
    {
        return $this->setDataType(DriverBase::DATA_TYPE_OBJECT);
    }

    /**
     * 返回实体对象
     * @param string $entityClass
     * @return $this
     * @throws Exception
     */
    public function asEntity($entityClass = null)
    {
        if (empty($entityClass)) {
            $entityClass = self::getDefaultEntityClass();
        }
        return $this->setDataType($entityClass);
    }

    /**
     * 设置返回数据类型
     * @param string $dataType
     * @return $this
     */
    public function setDataType($dataType)
    {
        $this->sql['option']['dataType'] = $dataType;
        return $this;
    }

    /**
     * 获取当前返回数据类型
     * @return array|bool|mixed|null
     */
    public function getDataType()
    {
        return isset($this->sql['option']['dataType']) ? $this->sql['option']['dataType'] : null;
    }

    /**
     * 选择多条记录
     * @param int $batchLimit
     * @param int $totalLimit
     * @return BatchQueryRecords
     */
    public function batch($batchLimit, $totalLimit = 0)
    {
        $sql = $this->sql ?: array();
        $model = new static();

        if (!empty($this->sql['sharding']['current'])) {
            $model->sharding($this->sql['sharding']['current']);
        }

        $model->setSql($sql);
        $model->clearSql(false);
        $this->clearSql();

        $records = new BatchQueryRecords($model, $batchLimit, $totalLimit);
        return $records;
    }

    /**
     * 选择多条记录
     * @return EachQueryRecords
     */
    public function each()
    {
        $sql = $this->sql ?: array();
        $model = new static();

        if (!empty($this->sql['sharding']['current'])) {
            $model->sharding($this->sql['sharding']['current']);
        }

        $model->setSql($sql);
        $model->clearSql(false);
        $this->clearSql();

        $records = new EachQueryRecords($model);
        return $records;
    }

    /**
     * 获取默认实体类
     * @return mixed
     * @throws Exception
     */
    public static function getDefaultEntityClass()
    {
        if (empty(static::$entity)) {
            ocService()->error->show('need_entity_class');
        }
        return static::$entity;
    }

    /**
     * 获取实体类
     * @return array|bool|mixed|string|null
     * @throws Exception
     */
    public function getEntityClass()
    {
        $entityClass = OC_EMPTY;
        $dataType = $this->getDataType();

        if (!empty($dataType)) {
            $simpleDataTypes = array(DriverBase::DATA_TYPE_ARRAY, DriverBase::DATA_TYPE_OBJECT);
            if (!in_array($dataType, $simpleDataTypes)) {
                $entityClass = $dataType;
            }
        }

        if (!$entityClass) {
            $entityClass = self::getDefaultEntityClass();
        }

        return $entityClass;
    }

    /**
     * 设置是否debug
     * @param bool $debug
     * @return $this
     */
    public function debug($debug = true)
    {
        $this->sql['debug'] = $debug;
        return $this;
    }

    /**
     * 是否调试SQL语句
     * @return bool
     */
    protected function isDebug()
    {
        return isset($this->sql['debug']) && $this->sql['debug'] === true;
    }

    /**
     * 查询多条记录
     * @param string|array $condition
     * @param string|array $option
     * @param array $executeOptions
     * @return array|bool
     * @throws Exception
     */
    public function getAll($condition = null, $option = null, $executeOptions = array())
    {
        return $this->baseFind($condition, $option, false, false, null, $executeOptions);
    }

    /**
     * 查询一条记录
     * @param string|array $condition
     * @param string|array $option
     * @param array $executeOptions
     * @return array|bool
     * @throws Exception
     */
    public function getRow($condition = null, $option = null, $executeOptions = array())
    {
        return $this->baseFind($condition, $option, true, false, null, $executeOptions);
    }

    /**
     * 获取某个字段值
     * @param string $field
     * @param bool $condition
     * @return bool|mixed
     * @throws Exception
     */
    public function getValue($field, $condition = false)
    {
        $isDebug = $this->isDebug();
        $row = $this->getRow($condition, $field);

        if ($isDebug) return false;

        if (is_object($row)) {
            return property_exists($row, $field) ? $row->$field : false;
        }

        $row = (array)$row;
        $result = isset($row[$field]) ? $row[$field] : false;
        return $result;
    }

    /**
     * 查询总数
     * @param array $executeOptions
     * @return int
     * @throws Exception
     */
    public function getTotal($executeOptions = array())
    {
        $queryRow = true;

        if (!empty($this->sql['option']['group'])) {
            $queryRow = false;
        }

        $result = $this->baseFind(false, false, $queryRow, true, null, $executeOptions);

        if ($result) {
            if (!$queryRow) {
                $result = reset($result);
            }
            return (integer)$result['total'];
        }

        return 0;
    }

    /**
     * 推入SQL选项
     * @param string|array $condition
     * @param string|array $option
     * @param bool $queryRow
     */
    public function pushSql($condition, $option, $queryRow)
    {
        if ($condition) $this->where($condition);

        if ($queryRow) {
            if (!empty($this->sql['option']['limit'])) {
                $this->sql['option']['limit'][1] = 1;
            } else {
                $this->limit(1);
            }
        }

        if ($option) {
            if (ocScalar($option)) {
                $this->fields($option);
            } else {
                foreach ($option as $key => $value) {
                    if (in_array($key, self::$optionMethods)) {
                        $value = (array)$value;
                        call_user_func_array(array($this, $key), $value);
                    }
                }
            }
        }
    }

    /**
     * 获取别名
     * @return mixed|string
     */
    public function getAlias()
    {
        return !empty($this->sql['alias']) ? $this->sql['alias'] : ($this->alias ?: 'a');
    }

    /**
     * 查询数据
     * @param string|array $condition
     * @param string|array $options
     * @param bool $isQueryRow
     * @param bool $isCount
     * @param null $dataType
     * @param array $executeOptions
     * @return array|bool
     * @throws Exception
     */
    protected function baseFind($condition, $options, $isQueryRow, $isCount = false, $dataType = null, $executeOptions = array())
    {
        $isFilterCondition = $this->filterCondition();
        $plugin = $this->connect(false);
        $this->getFieldsInfo();
        $dataType = $dataType ?: ($this->getDataType() ?: DriverBase::DATA_TYPE_ARRAY);

        $this->pushSql($condition, $options, $isQueryRow);
        $this->setJoin($this->tag, null, $this->getAlias());
        $this->fire(self::EVENT_BEFORE_SELECT_QUERY, array($isQueryRow, $isCount));

        $generator = $this->getSqlGenerator($plugin);
        $closeUnion = isset($executeOptions['close_union']) && $executeOptions['close_union'] === true;

        if ($closeUnion) {
            $unions = array();
            $isUnion = false;
        } else {
            $unions = $this->getUnions();
            $isUnion = !!$unions;
        }

        $isDebug = $this->isDebug();
        $sqlData = $generator->genSelectSql($isCount, $unions, $isFilterCondition);
        $this->lastSql = array($sqlData);

        if ($isDebug) {
            $this->debug(false);
            return false;
        }

        $shardingCurrent = $this->getSharding();
        if ($isQueryRow) {
            $result = $plugin->queryRow($sqlData, $isCount, $isUnion, $dataType, $shardingCurrent);
        } else {
            $result = $plugin->query($sqlData, $isCount, $isUnion, $dataType, $shardingCurrent);
        }

        if ($result === false) {
            ocService()->errow->show('failed_database_query', array(json_encode($plugin->getError())));
        }

        if (!$isCount && !$isQueryRow && $this->isPage()) {
            $result = array('total' => $this->getTotal(), 'data' => $result);
            $this->lastSql = array($sqlData, $this->getLastSql());
        }

        $this->fire(self::EVENT_AFTER_SELECT_QUERY, array($result, $isQueryRow, $isCount));

        $this->clearSql();
        return $result;
    }

    /**
     * 获取SQL生成器
     * @param DatabaseBase $plugin
     * @return Generator
     * @throws Exception
     */
    public function getSqlGenerator($plugin)
    {
        $generator = new Generator($plugin);

        $generator->setDatabaseName($this->databaseName);
        $generator->setTable($this->tableName);
        $generator->setAlias($this->alias);
        $generator->setSql($this->sql);
        $generator->setPrimaries(static::getPrimaries());
        $generator->setFields($this->getFieldsInfo());
        $generator->setMaps(static::getConfig('MAPS'));
        $generator->setJoins(static::getConfig('JOINS'));

        return $generator;
    }

    /**
     * 是否分页
     * @return bool
     */
    public function isPage()
    {
        return !empty($this->sql['option']['page']);
    }

    /**
     * 连接数据库
     * @param bool $isMaster
     * @return mixed|null
     * @throws Exception
     */
    public function connect($isMaster = true)
    {
        if (isset($this->sql['option']['master'])) {
            $isMaster = $this->sql['option']['master'] !== false;
        }

        $this->fire(self::EVENT_BEFORE_CONNECT, array($isMaster));

        if ($isMaster) {
            $plugin = ocService()->databases->make($this->serverName);
        } else {
            $plugin = ocService()->databases->make($this->serverName, false, false);
        }

        if (!$plugin->isSelectedDatabase()) {
            $plugin->selectDatabase();
        }

        $this->fire(self::EVENT_AFTER_CONNECT, array($plugin, $isMaster));

        return $plugin;
    }

    /**
     * 左联接
     * @param string $class
     * @param string $alias
     * @param string $on
     * @return $this
     * @throws Exception
     */
    public function leftJoin($class, $alias = null, $on = null)
    {
        return $this->setJoin($class, 'left', $alias, $on);
    }

    /**
     * 右联接
     * @param string $class
     * @param string $alias
     * @param string $on
     * @return $this
     * @throws Exception
     */
    public function rightJoin($class, $alias = null, $on = null)
    {
        return $this->setJoin($class, 'right', $alias, $on);
    }

    /**
     * 内全联接
     * @param string $class
     * @param string $alias
     * @param string $on
     * @return $this
     * @throws Exception
     */
    public function innerJoin($class, $alias = null, $on = null)
    {
        return $this->setJoin($class, 'inner', $alias, $on);
    }

    /**
     * 设置别名
     * @param string $alias
     * @return $this
     */
    public function alias($alias)
    {
        $this->sql['alias'] = $alias;
        return $this;
    }

    /**
     * 附加字段
     * @param string|array $fields
     * @param string $alias
     * @return $this
     */
    public function fields($fields, $alias = null)
    {
        if ($fields) {
            $fields = array($alias, $fields);
            $this->sql['option']['fields'][] = $fields;
        }

        return $this;
    }

    /**
     * 附加联接关系
     * @param string $on
     * @param string $alias
     * @return $this
     */
    protected function addOn($on, $alias = null)
    {
        $this->sql['tables'][$alias]['on'] = $on;
        return $this;
    }

    /**
     * 生成AND Between条件
     * @param string $field
     * @param string $value1
     * @param string $value2
     * @param string $alias
     * @return $this
     */
    public function between($field, $value1, $value2, $alias = null)
    {
        $where = array($alias, 'between', array($field, $value1, $value2), 'AND');
        $this->sql['option']['where'][] = $where;

        return $this;
    }

    /**
     * 生成OR Between条件
     * @param string $field
     * @param string $value1
     * @param string $value2
     * @param string $alias
     * @return $this
     */
    public function orBetween($field, $value1, $value2, $alias = null)
    {
        $where = array($alias, 'between', array($field, $value1, $value2), 'OR');
        $this->sql['option']['where'][] = $where;

        return $this;
    }

    /**
     * 提取条件摘录
     * @param array $data
     * @param string $type
     * @return $this
     */
    public function extractWhere($data, $type = 'where')
    {
        if (is_array($data)) {
            if (empty($this->sql['extracts'][$type])) {
                $this->sql['extracts'][$type] = array();
            }
            $this->sql['extracts'][$type] = array_merge($this->sql['extracts'][$type], $data);
        }
        return $this;
    }

    /**
     * 获取条件摘录
     * @return array
     */
    public function getExtracts()
    {
        $where = !empty($this->sql['extracts']['where']) ? $this->sql['extracts']['where'] : array();
        $moreWhere = !empty($this->sql['extracts']['moreWhere']) ? $this->sql['extracts']['moreWhere'] : array();

        return array(
            'where' => $where,
            'moreWhere' => $moreWhere
        );
    }

    /**
     * 添加条件
     * @param string|array $whereOrField
     * @param string $signOrAlias
     * @param mixed $value
     * @param string $alias
     * @return $this
     */
    public function where($whereOrField, $signOrAlias = null, $value = null, $alias = null)
    {
        if (func_num_args() < 3) {
            $alias = $signOrAlias;
            if (!ocEmpty($whereOrField)) {
                $where = array($alias, 'where', $whereOrField, 'AND');
                $this->sql['option']['where'][] = $where;
            }
            $this->extractWhere($whereOrField);
        } else {
            $sign = array('AND', $signOrAlias);
            $this->complexWhere('where', $sign, $whereOrField, $value, $alias);
            $this->extractWhere(array($whereOrField => $value));
        }

        return $this;
    }

    /**
     * 添加OR条件
     * @param string|array $whereOrField
     * @param string $signOrAlias
     * @param string $value
     * @param string $alias
     * @return $this
     */
    public function orWhere($whereOrField, $signOrAlias = null, $value = null, $alias = null)
    {
        if (func_num_args() < 3) {
            $alias = $signOrAlias;
            if (!ocEmpty($whereOrField)) {
                $where = array($alias, 'where', $whereOrField, 'OR');
                $this->sql['option']['where'][] = $where;
            }
        } else {
            $sign = array('OR', $signOrAlias);
            $this->complexWhere('where', $sign, $whereOrField, $value, $alias);
        }

        return $this;
    }

    /**
     * 生成复杂条件
     * @param string $type
     * @param array $signInfo
     * @param string $field
     * @param mixed $value
     * @param string $alias
     * @return $this
     */
    public function complexWhere($type, array $signInfo, $field, $value, $alias = null)
    {
        if (isset($signInfo[1])) {
            list($linkSign, $operator) = $signInfo;
        } else {
            $linkSign = 'AND';
            $operator = $signInfo[0];
        }

        $linkSign = strtoupper($linkSign);
        $where = array($alias, 'cWhere', array($operator, $field, $value), $linkSign);
        $this->sql['option'][$type][] = $where;

        return $this;
    }

    /**
     * 更多条件
     * @param string $where
     * @param string $link
     * @return $this
     */
    public function moreWhere($where, $link = null)
    {
        $link = $link ?: 'AND';
        $this->sql['option']['moreWhere'][] = compact('where', 'link');
        $this->extractWhere($where, 'moreWhere');
        return $this;
    }

    /**
     * 尾部更多SQL语句
     * @param string $sql
     * @return $this
     */
    public function more($sql)
    {
        $sql = (array)$sql;
        foreach ($sql as $value) {
            $this->sql['option']['more'][] = $value;
        }
        return $this;
    }

    /**
     * 分组
     * @param string $groupBy
     * @return $this
     */
    public function groupBy($groupBy)
    {
        if ($groupBy) {
            $this->sql['option']['group'] = $groupBy;
        }
        return $this;
    }

    /**
     * AND分组条件
     * @param $whereOrField
     * @param string $sign
     * @param mixed $value
     * @return $this
     */
    public function having($whereOrField, $sign = null, $value = null)
    {
        if (func_num_args() < 3) {
            if (!ocEmpty($whereOrField)) {
                $where = array(false, 'where', $whereOrField, 'AND');
                $this->sql['option']['having'][] = $where;
            }
        } else {
            $sign = array('AND', $sign);
            $this->complexWhere('having', $sign, $whereOrField, $value, false);
        }

        return $this;
    }

    /**
     * OR分组条件
     * @param $whereOrField
     * @param string $sign
     * @param mixed $value
     * @return $this
     */
    public function orHaving($whereOrField, $sign = null, $value = null)
    {
        if (func_num_args() < 3) {
            if (!ocEmpty($whereOrField)) {
                $where = array(false, 'where', $whereOrField, 'OR');
                $this->sql['option']['having'][] = $where;
            }
        } else {
            $sign = array('OR', $sign);
            $this->complexWhere('having', $sign, $whereOrField, $value, false);
        }

        return $this;
    }

    /**
     * 添加排序
     * @param string $orderBy
     * @return $this
     */
    public function orderBy($orderBy)
    {
        if ($orderBy) {
            $this->sql['option']['order'] = $orderBy;
        }
        return $this;
    }

    /**
     * 添加union排序
     * @param string $orderBy
     * @return $this
     */
    public function unionOrderBy($orderBy)
    {
        if ($orderBy) {
            $this->sql['unions']['option']['order'] = $orderBy;
        }
        return $this;
    }

    /**
     * 添加limit
     * @param int $offset
     * @param int $rows
     * @return $this
     */
    public function limit($offset, $rows = null)
    {
        if (isset($rows)) {
            $rows = $rows ?: 1;
        } else {
            if (is_array($offset)) {
                if (isset($offset[1])) {
                    $rows = $offset[1];
                    $offset = $offset[0];
                } else {
                    $rows = isset($offset[0]) ? $offset[0] : 0;
                    $offset = 0;
                }
            } else {
                $rows = $offset;
                $offset = 0;
            }
        }

        $this->sql['option']['limit'] = array($offset, $rows);
        return $this;
    }

    /**
     * 添加union limit
     * @param int $offset
     * @param int $rows
     * @return $this
     */
    public function unionLimit($offset, $rows = null)
    {
        if (isset($rows)) {
            $rows = $rows ?: 1;
        } else {
            $rows = $offset;
            $offset = 0;
        }

        $this->sql['unions']['option']['limit'] = array($offset, $rows);
        return $this;
    }

    /**
     * 分页处理
     * @param array $limitInfo
     * @return DatabaseModel
     */
    public function page(array $limitInfo)
    {
        $this->sql['option']['page'] = true;
        return $this->limit($limitInfo['offset'], $limitInfo['rows']);
    }

    /**
     * 清除查询选项
     * @param $optionName
     * @param bool $delete
     * @return $this
     * @throws Exception
     */
    public function stripOptions($optionName, $delete = false)
    {
        if (!empty($this->sql['option'])) {
            if ($delete) {
                ocDel($this->sql['option'], $optionName);
            } else {
                ocSet($this->sql['option'], $optionName, array());
            }
        }
        return $this;
    }

    /**
     * 绑定占位符参数
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function bind($name, $value)
    {
        $this->sql['binds'][$name] = $value;
        return $this;
    }

    /**
     * * 设置统计字段
     * @param string $field
     * @return $this
     */
    public function countField($field)
    {
        $this->sql['countField'] = $field;
        return $this;
    }

    /**
     * 获取最后执行的SQL
     * @return array
     */
    public function getLastSql()
    {
        return $this->lastSql;
    }

    /**
     * 获取表名
     * @return string
     */
    public static function getTable()
    {
        return static::$table;
    }

    /**
     * 合并查询（去除重复值）
     * @param DatabaseModel $model
     * @return $this
     */
    public function union(DatabaseModel $model)
    {
        $this->baseUnion($model, false);
        return $this;
    }

    /**
     * 合并查询
     * @param DatabaseModel $model
     * @return $this
     */
    public function unionAll(DatabaseModel $model)
    {
        $this->baseUnion($model, true);
        return $this;
    }

    /**
     * 合并查询
     * @param string|object $model
     * @param bool $unionAll
     */
    public function baseUnion($model, $unionAll = false)
    {
        $this->sql['unions']['models'][] = compact('model', 'unionAll');
    }

    /**
     * 获取合并设置
     * @return array
     */
    public function getUnions()
    {
        return !empty($this->sql['unions']) ? $this->sql['unions'] : array();
    }

    /**
     * 联接查询
     * @param string $class
     * @param string $type
     * @param string $alias
     * @param bool $on
     * @return $this
     * @throws Exception
     */
    protected function setJoin($class, $type, $alias, $on = false)
    {
        $config = array();

        if ($type == false) {
            $fullname = $this->getTableName();
            $alias = $alias ?: $fullname;
            $class = $this->tag;
            $tables = array($alias => compact('type', 'fullname', 'class', 'config'));
            if (!empty($this->sql['tables'])) {
                $this->sql['tables'] = array_merge($tables, $this->sql['tables']);
            } else {
                $this->sql['tables'] = $tables;
            }
        } else {
            $config = $this->getRelateConfig($class);
            if ($config) {
                $class = $config['class'];
            }
            if (empty($class) || !class_exists($class)) {
                ocService()->error->show('not_exists_model_join', array($class));
            }
            $fullname = $class::getDefaultTableName();
            $alias = $alias ?: $fullname;
            $this->sql['tables'][$alias] = compact('type', 'fullname', 'class', 'config');
        }

        if ($on) {
            $this->addOn($on, $alias);
        } elseif ($config) {
            $this->addOn(OC_EMPTY, $alias);
        }

        return $this;
    }

    /**
     * 获取关联配置
     * @param string $key
     * @return array|mixed
     * @throws Exception
     */
    public function getRelateConfig($key)
    {
        $relations = $this->getConfig('RELATIONS');

        if (empty($relations[$key])) {
            return array();
        }

        $config = $relations[$key];
        if (count($config) < 3) {
            ocService()->error->show('fault_relate_config');
        }

        list($joinType, $class, $joinOn) = $config;
        $condition = isset($config[3]) ? $config[3] : null;

        if (!is_array($joinOn)) {
            $joinOn = explode(',', $joinOn);
        }

        $joinOn = array_map('trim', $joinOn);

        if (isset($joinOn[1])) {
            list($primaryKey, $foreignKey) = $joinOn;
        } else {
            $primaryKey = $foreignKey = reset($joinOn);
        }

        $config = compact('joinType', 'class', 'primaryKey', 'foreignKey', 'condition');
        return $config;
    }

    /**
     * 魔术方法-调用未定义的静态方法时
     * @param string $name
     * @param array $params
     * @return mixed|void
     * @throws Exception
     */
    public static function __callStatic($name, $params)
    {
        $regExp = '/^((selectOne)|(selectAll)|(getRow)|(getAll))By(\w+)$/';

        if (preg_match($regExp, $name, $matches)) {
            $method = $matches[1];
            $fieldName = lcfirst($matches[6]);
            return self::queryDynamic($method, $fieldName, $params);
        }

        return parent::__callStatic($name, $params);
    }

    /**
     * 动态查询
     * @param $method
     * @param $fieldName
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    protected static function queryDynamic($method, $fieldName, array $params = array())
    {
        if (empty($params)) {
            ocService()->error->show('need_find_value');
        }

        $value = reset($params);
        if (!ocSimple($value)) {
            ocService()->error->show('fault_find_value');
        }

        $model = new static();
        $fields = $model->getFieldsInfo();
        $fieldName = ocHumpToLine($fieldName);

        if (array_key_exists($fieldName, $fields)) {
            $result = $model
                ->where(array($fieldName => $value))
                ->$method();
            return $result;
        }

        ocService()->error->show('not_exists_find_field');
    }
}
