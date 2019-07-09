<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   数据库模型类Database
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Models;

use Ocara\Core\DriverBase;
use Ocara\Core\FormManager;
use \ReflectionObject;
use Ocara\Exceptions\Exception;
use Ocara\Core\CacheFactory;
use Ocara\Core\FormToken;
use Ocara\Core\DatabaseFactory;
use Ocara\Core\DatabaseBase;
use Ocara\Core\ModelBase;
use Ocara\Iterators\Database\ObjectRecords;
use Ocara\Iterators\Database\BatchObjectRecords;
use Ocara\Iterators\Database\EachObjectRecords;

defined('OC_PATH') or exit('Forbidden!');

abstract class DatabaseModel extends ModelBase
{

	/**
	 * @var @primary 主键字段列表
	 * @var $primaries 主键字段数组
	 */
	protected $plugin;
	protected $primary;
	protected $table;
	protected $alias;
    protected $module;
    protected $connectName = 'defaults';

    protected $tag;
    protected $master;
    protected $slave;
    protected $database;
    protected $tableName;
    protected $autoIncrementField;

    protected $sql = array();
    protected $fields = array();
    protected $primaries = array();
    protected $joins = array();
    protected $unions = array();
    protected $relateShardingData = array();
    protected $relateShardingInfo = array();

    protected static $config = array();
    protected static $configPath = array();
    protected static $requirePrimary;

    const EVENT_CACHE_QUERY_DATA = 'cacheQueryData';

    /**
     * 初始化
     * DatabaseModel constructor.
     * @param array $data
     */
	public function __construct(array $data = array())
	{
		$this->init();
		if ($data) $this->data($data);
	}

	/**
	 * 初始化
	 */
	public function init()
	{
		if (self::$requirePrimary === null) {
			$required = ocConfig('MODEL_REQUIRE_PRIMARY', false);
			self::$requirePrimary = $required ? true : false;
		}

		if (self::$requirePrimary && empty($this->primary)) {
			ocService()->error->show('no_primaries');
		}

		$this->tag = self::getClass();
		$this->alias = $this->alias ? $this->alias : 'a';
		$this->tableName = empty($this->table) ? lcfirst(self::getClassName()) : $this->table;

		$this->setJoin(false, $this->tag, $this->alias);

		if ($this->primary) {
			$this->primaries = explode(',', $this->primary);
		}

		if (method_exists($this, '__start')) $this->__start();
		if (method_exists($this, '__model')) $this->__model();

		return $this;
	}

    /**
     * 注册事件
     * @throws Exception
     */
    public function registerEvents()
    {
        $this->bindEvents($this);
        $this->event(self::EVENT_CACHE_QUERY_DATA)
             ->append(ocConfig(array('EVENT', 'model', 'query', 'save_cache_data'), null));
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
	 */
	public function getTableName()
	{
		return $this->tableName;
	}

    /**
     * 获取表的全名（包括前缀）
     * @return mixed
     * @throws Exception
     */
	public function getTableFullname()
	{
		return $this->connect()->getTableFullname($this->tableName);
	}

	/**
	 * 获取当前服务器
	 * @return mixed
	 */
	public function getConnectName()
	{
		return $this->connectName;
	}

	/**
	 * 获取当前数据库
	 * @return mixed
	 */
	public function getDatabase()
	{
		return $this->database;
	}

    /**
     * 获取主键
     * @return array
     */
	public function getPrimaries()
    {
        return $this->primaries;
    }

    /**
     * 执行分库分表
     * @param array $data
     * @param null $relationName
     * @return $this
     */
	public function sharding(array $data = array(), $relationName = null)
	{
	    if (func_num_args() >= 2) {
	        if ($relationName) {
                $this->relateShardingInfo[$relationName] = $data;
            }
        } else {
            if (method_exists($this, '__sharding')) {
                $this->__sharding($data);
            }
        }

		return $this;
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
     * @return array|mixed
     */
	public function getModelConfig()
	{
        $paths = $this->getConfigPath();

        $modelConfig = array(
            'MAPS' => $this->fieldsMap() ? : array(),
            'RELATIONS' => $this->relations() ? : array(),
            'RULES' => $this->rules() ? : array(),
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

		if (isset($key)) {
            $key = strtoupper($key);
			if ($field) {
				return ocGet(array($key, $field), self::$config[$tag]);
			}
			return ocGet($key, self::$config[$tag], array());
		}

		return self::$config[$tag];
	}

    /**
     * 获取配置文件路径
     * @return array|mixed
     */
	public function getConfigPath()
	{
	    $tag = $this->tag;

	    if (!empty(self::$configPath[$tag])) {
	        return self::$configPath[$tag];
        }

        $moduleLang = OC_EMPTY;
        $language = ocService()->app->getLanguage();

        $ref = new ReflectionObject($this);
        $filePath = ocCommPath($ref->getFileName());
        $file = basename($filePath);
        $dir = dirname($filePath) . OC_DIR_SEP;

        if ($this->module) {
            list($rootPath, $subDir) = ocSeparateDir($dir, '/privates/model/database/');
            $modulePath = OC_MODULE_PATH ? : ocPath('modules');
            $moduleLang = $modulePath . '/' . $this->module . '/privates/lang/' . $language . '/database/' . $subDir . $file;
        } else {
            list($rootPath, $subDir) = ocSeparateDir($dir, '/application/model/database/');
        }

        $subDir = rtrim($subDir, '/');
        $paths = array(
            'lang' => ocPath('lang', ocDir($language, 'database', $subDir) . $file),
            'fields' => ocPath('fields',  ocDir($subDir) . $file),
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
     * @return mixed
     */
	public static function mapField($field)
    {
        $config = self::getConfig('MAPS');
        $result = isset($config[$field]) ? $config[$field] : $field;
        return $result;
    }

    /**
     * 获取当前数据库对象
     * @param bool $slave
     * @return mixed
     */
	public function db($slave = false)
	{
		$name = $slave ? 'slave' : 'plugin';
		if (is_object($this->$name)) {
			return $this->$name;
		}

		ocService()->error->show('null_database');
	}

	/**
	 * 切换数据库
	 * @param string $name
	 */
	public function selectDatabase($name)
	{
		$this->database = $name;
	}

	/**
	 * 切换数据表
	 * @param $name
	 * @param null $primary
	 */
	public function selectTable($name, $primary = null)
	{
		$this->table = $name;
		$this->tableName = $name;

		if ($primary) {
			$this->primary = $primary;
			if ($this->primary) {
				$this->primaries = explode(',', $this->primary);
			}
		}

		$this->sql['tables'][$this->alias]['fullname'] = $this->getTableName();
	}

    /**
     * 从数据库获取数据表的字段
     * @param bool $cache
     * @return $this
     * @throws Exception
     */
	public function loadFields($cache = true)
	{
		if ($cache) {
			$this->fields = $this->getFieldsConfig();
		}

		if (!$this->fields) {
			$fieldsInfo = $this->connect()->getFields($this->tableName);
            $this->autoIncrementField = $fieldsInfo['autoIncrementField'];
            $this->fields = $fieldsInfo['list'];
		}

		return $this;
	}

    /**
     * 获取字段配置
     * @return array|mixed
     */
	public function getFieldsConfig()
	{
		$paths = $this->getConfigPath();
		$path = ocLowerFile($paths['fields']);

		if (ocFileExists($path)) {
			return @include($path);
		}

		return array();
	}

	/**
	 * 获取字段
	 */
	public function getFields()
	{
		if (empty($this->fields)) {
			$this->loadFields();
		}

		return $this->fields;
	}

    /**
     * 获取字段
     */
    public function getFieldsName()
    {
        return array_keys($this->getFields());
    }

    /**
     * 获取自增字段名
     */
    public function getAutoIncrementField()
    {
        return $this->autoIncrementField;
    }

    /**
     * 别名字段数据映射过滤
     * @param array $data
     * @return array
     * @throws Exception
     */
	public function filterData(array $data)
	{
        $plugin = $this->plugin(false);
		$result = array();

		foreach ($data as $field => $value) {
		    if (!is_object($value)) {
                $key = $this->filterField($field);
                if ($key) {
                    $result[$key] = $value;
                }
            }
		}

		if ($this->fields) {
			if (!is_object($plugin)) {
                $plugin = $this->setPlugin($this->connect());
			}
			$result = $plugin->formatFieldValues($this->fields, $result);
		}

		return $result;
	}

    /**
     * 字段别名映射过滤
     * @param $field
     * @return mixed|null
     * @throws Exception
     */
	public function filterField($field)
	{
	    $plugin = $this->plugin();

		if (!$this->fields) {
			$this->loadFields();
		}

		$mapsConfig = self::getConfig('MAPS');
		$key = isset($mapsConfig[$field]) ? $mapsConfig[$field] : $field;

        if (!$plugin->hasAlias($key)) {
            if (!isset($this->fields[$key])) {
                return null;
            }
        }

		return $key;
	}

	/**
	 * 清理SQL
	 */
	public function clearSql()
	{
		$this->sql = array();
		$this->setJoin(false, $this->tableName, $this->alias);
		$this->setPlugin($this->master);
		return $this;
	}

	/**
	 * 缓存查询的数据
	 * @param string $server
	 * @param bool $required
	 * @return $this
	 */
	public function cache($server = null, $required = false)
	{
		$server = $server ? : DatabaseFactory::getDefaultServer();
		$this->sql['cache'] = array($server, $required);
		return $this;
	}

	/**
	 * 规定使用主库查询
	 */
	public function master()
	{
		$this->sql['option']['master'] = true;
		return $this;
	}

    /**
     * 保存记录
     * @param $data
     * @param $condition
     * @param bool $debug
     * @return bool
     * @throws Exception
     */
    protected function baseSave($data, $condition, $debug = false)
    {
        $plugin = $this->plugin();
        $data = $this->filterData($data);

        if (empty($data)) {
            ocService()->error->show('fault_save_data');
        }

        $this->pushTransaction();

        if ($condition) {
            $result = $plugin->update($this->tableName, $data, $condition, $debug);
        } else {
            $result = $plugin->insert($this->tableName, $data, $debug);
        }

        $this->clearSql();

        if ($debug === DatabaseBase::DEBUG_RETURN) return $result;

        $result = $plugin->errorExists() ? false : true;
        return $result;
    }

	/**
	 * 预处理
	 * @param bool $prepare
	 */
	public function prepare($prepare = true)
	{
		$this->plugin()->is_prepare($prepare);
	}

    /**
     * 推入事务池中
     */
	public function pushTransaction()
    {
	    $this->connect();
        ocService()->transaction->push($this->plugin());
    }

    /**
     * 新建记录
     * @param array $data
     * @param bool $debug
     * @return bool
     * @throws Exception
     */
	public function create(array $data, $debug = false)
	{
		$this->connect();
        $result = $this->baseSave($data, false, $debug);
		return $result;
	}

    /**
     * 更新记录
     * @param array $data
     * @param bool $debug
     * @return bool
     * @throws Exception
     */
	public function update(array $data, $debug = false)
	{
        $condition = $this->getCondition();
		if (empty($condition)) {
		    ocService()->error->show('need_condition');
		}

		$result = $this->baseSave($data, $condition, $debug);
		return $result;
	}

    /**
     * 删除记录
     * @param bool $debug
     * @return bool
     * @throws Exception
     */
	public function delete($debug = false)
	{
	    $plugin = $this->plugin();
		$condition = $this->getCondition();
		if (empty($condition)) {
			ocService()->error->show('need_condition');
		}

        $this->pushTransaction();
		$result = $plugin->delete($this->tableName, $condition, $debug);

		if ($debug === DatabaseBase::DEBUG_RETURN) {
			return $result;
		}

		$this->clearSql();

		$result = $plugin->errorExists() ? false : true;
		return $result;
	}

    /**
     * 获取操作条件
     * @return mixed
     * @throws Exception
     */
    protected function getCondition()
	{
		$this->connect();
		$condition = $this->genWhere();

		return $condition;
	}

    /**
     * 用SQL语句获取多条记录
     * @param $sql
     * @param bool $debug
     * @return bool
     * @throws Exception
     */
	public function query($sql, $debug = false)
	{
        $plugin = $this->plugin();

		if ($sql) {
			$sqlData = $plugin->getSqlData($sql);
			$dataType = $this->sql['option']['dataType'] ? : DriverBase::DATA_TYPE_ARRAY;
			return $this
                ->connect(false)
                ->query($sqlData, $debug, false, array(), $dataType);
		}

		return false;
	}

    /**
     * 用SQL语句获取一条记录
     * @param $sql
     * @param bool $debug
     * @return bool
     * @throws Exception
     */
	public function queryRow($sql, $debug = false)
	{
        $plugin = $this->plugin();

		if ($sql) {
			$sqlData = $plugin->getSqlData($sql);
            $dataType = $this->sql['option']['dataType'] ? : DriverBase::DATA_TYPE_ARRAY;
			return $this
                ->connect(false)
                ->query($sqlData, $debug, false, array(), $dataType);
		}

		return false;
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
	 */
	public function setSql($sql)
	{
		$this->sql = $sql;
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
     * 按条件选择首行
     * @param bool $condition
     * @param null $options
     * @param bool $debug
     * @return $this|array|null
     * @throws Exception
     */
	public function findRow($condition = false, $options = null, $debug = false)
	{
		if ($condition) {
			$this->where($condition);
		}

        $dataType = $this->getDataType();
        $result = $this->baseFind($condition, $options, $debug, true, false, $dataType);

        return $result;
	}

    /**
     * 选择多条记录
     * @param null $condition
     * @param null $options
     * @param bool $debug
     * @return array
     * @throws Exception
     */
	public function findAll($condition = null, $options = null, $debug = false)
	{
        $records = $this->baseFind($condition, $options, $debug, false, false, self::getClass());
		return $records;
	}

    /**
     * 返回对象
     * @return $this
     */
    public function asArray()
    {
        $this->sql['option']['dataType'] = DriverBase::DATA_TYPE_ARRAY;
        return $this;
    }

    /**
     * 返回对象
     * @return $this
     */
	public function asObject()
    {
        $this->sql['option']['dataType'] = DriverBase::DATA_TYPE_OBJECT;
        return $this;
    }

    /**
     * 返回实体对象
     * @param null $entityClass
     * @return $this
     */
    public function asEntity($entityClass = null)
    {
        if (empty($entityClass)) {
            $entityClass = ocNamespace(__NAMESPACE__, 'entities',  __CLASS__ . 'Entity');
        }
        $this->sql['option']['dataType'] = $entityClass;
        return $this;
    }

    /**
     * 获取当前返回数据类型
     * @param null $default
     * @return array|bool|mixed|null
     */
    public function getDataType($default = null)
    {
        $default = $default ? : DriverBase::DATA_TYPE_ARRAY;
        return ocGet(array('option', 'dataType'), $this->sql, $default);
    }

    /**
     * 选择多条记录
     * @param integer $offset
     * @param integer $rows
     * @param bool $debug
     * @return BatchObjectRecords
     */
    public function batch($offset, $rows = null, $debug = false)
    {
        if (!isset($limit)) {
            $rows = $offset;
            $offset = 0;
        }

        $sql = $this->sql ? : array();
        $records = new BatchObjectRecords(self::getClass(), $this->getEntityClass(), $offset, $rows, $sql, $debug);

        return $records;
    }

    /**
     * 选择多条记录
     * @param int $offset
     * @param bool $debug
     * @return EachObjectRecords
     */
    public function each($offset = 0, $debug = false)
    {
        $sql = $this->sql ? : array();
        $records = new EachObjectRecords(self::getClass(), $this->getEntityClass(), $offset, $sql, $debug);

        return $records;
    }

    /**
     * 获取实体类
     * @return bool
     */
    public function getEntityClass()
    {
        $entityClass = OC_EMPTY;

        if ($this->sql['option']['dataType']) {
            $simpleDataTypes = array(DriverBase::DATA_TYPE_ARRAY, DriverBase::DATA_TYPE_OBJECT);
            if (!in_array($this->sql['option']['dataType'], $simpleDataTypes)) {
                $entityClass = $this->sql['option']['dataType'];
            }
        }

        if (!$entityClass) {
            $class = self::getClass();
            if (substr($class, -6) == 'Entity') {
                $entityClass = $class;
            }
        }

        if (!$entityClass) {
            $entityClass = ocNamespace(__NAMESPACE__, 'entities',  __CLASS__ . 'Entity');
        }

        return $entityClass;
    }

    /**
     * 查询多条记录
     * @param mixed $condition
     * @param mixed $option
     * @param bool $debug
     * @return array
     * @throws Exception
     */
	public function getAll($condition = null, $option = null, $debug = false)
	{
		return $this->baseFind($condition, $option, $debug, false, false, DriverBase::DATA_TYPE_ARRAY);
	}

    /**
     * 查询一条记录
     * @param mixed $condition
     * @param mixed $option
     * @param bool $debug
     * @return array
     * @throws Exception
     */
	public function getRow($condition = null, $option = null, $debug = false)
	{
		return $this->baseFind($condition, $option, $debug, true, false, DriverBase::DATA_TYPE_ARRAY);
	}

    /**
     * 获取某个字段值
     * @param string $field
     * @param bool $condition
     * @param bool $debug
     * @return array|mixed|string|null
     * @throws Exception
     */
	public function getValue($field, $condition = false, $debug = false)
	{
		$row = $this->getRow($condition, $field, $debug);

		if ($debug === DatabaseBase::DEBUG_RETURN) return $row;

		if (is_object($row)) {
			return property_exists($row, $field) ? $row->$field : null;
		}

		$row = (array)$row;
		return isset($row[$field]) ? $row[$field] : OC_EMPTY;
	}

    /**
     * 查询总数
     * @param bool $debug
     * @return array|int|mixed
     * @throws Exception
     */
	public function getTotal($debug = false)
	{
		$queryRow = true;
		if ($this->unions || !empty($this->sql['option']['group'])) {
			$queryRow = false;
		}

		$result = $this->baseFind(false, false, $debug, $queryRow, true, DriverBase::DATA_TYPE_ARRAY);

		if ($debug === DatabaseBase::DEBUG_RETURN) {
			return $result;
		}

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
     * @param $condition
     * @param $option
     * @param $queryRow
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
                    if (method_exists($this, $key)) {
                        $value = (array)$value;
                        call_user_func_array(array($this, $key), $value);
                    }
                }
            }
        }
    }

    /**
     * 查询数据
     * @param mixed $condition
     * @param mixed $option
     * @param bool $debug
     * @param bool $queryRow
     * @param bool $count
     * @param null $dataType
     * @return array
     * @throws Exception
     */
    protected function baseFind($condition, $option, $debug, $queryRow, $count = false, $dataType = null)
	{
        $plugin = $this->plugin();
        $cacheInfo = null;
        $cacheObj = null;
        $encodeSql = null;

	    $this->pushSql($condition, $option, $queryRow);
        $sql = $this->genSelectSql($count);

		if (isset($this->sql['cache']) && is_array($this->sql['cache'])) {
			$cacheInfo = $this->sql['cache'];
		}

		list($cacheConnect, $cacheRequired) = $cacheInfo;
		$ifCache = empty($debug) && $cacheConnect;

		if ($ifCache) {
			$encodeSql = md5($sql);
			$cacheObj  = CacheFactory::connect($cacheConnect, $cacheRequired);
			$cacheData = $this->getCacheData($cacheObj, $encodeSql, $cacheObj, $cacheRequired);
			if ($cacheData) return $cacheData;
		}

        $dataType = $dataType ? : $this->getDataType();
		if ($queryRow) {
            $result = $plugin->queryRow($sql, $debug, $count, $this->unions, $dataType);
		} else {
            $result = $plugin->query($sql, $debug, $count, $this->unions, $dataType);
		}

		if ($debug === DatabaseBase::DEBUG_RETURN) {
			return $result;
		}

		if (!$count && !$queryRow && $this->isPage()) {
			$result = array('total' => $this->getTotal($debug), 'data'	=> $result);
		}

		$this->clearSql();

		if ($ifCache && is_object($cacheObj)) {
			$this->saveCacheData($cacheObj, $sql, $encodeSql, $cacheRequired, $result);
		}

		return $result;
	}

    /**
     * 是否分页
     * @return bool
     */
	public function isPage()
    {
        return ocGet(array('option', 'page'), $this->sql) ? true : false;
    }

    /**
     * 连接数据库
     * @param bool $master
     * @return mixed|null
     * @throws Exception
     */
	public function connect($master = true)
	{
        $plugin = $this->setPlugin(null);

		if (!($master || ocGet(array('option', 'master'), $this->sql))) {
			if (!is_object($this->slave)) {
				$this->slave = DatabaseFactory::create($this->connectName, false, false);
			}
            $plugin = $this->setPlugin($this->slave);
		}

		if (!is_object($plugin)) {
			if (!is_object($this->master)) {
				$this->master = DatabaseFactory::create($this->connectName);
			}
            $plugin = $this->setPlugin($this->master);
		}

		if ($this->database) {
            $plugin->selectDatabase($this->database);
		}

		return $plugin;
	}

    /**
     * 获取缓存数据
     * @param object $cacheObj
     * @param string $sql
     * @param string $sqlEncode
     * @param bool $cacheRequired
     * @return mixed|null
     * @throws Exception
     */
	public function getCacheData($cacheObj, $sql, $sqlEncode, $cacheRequired)
	{
		if (is_object($cacheObj)) {
			if ($callback = ocConfig(array('EVENT', 'model', 'query', 'get_cache_data'), null)) {
				$params = array($cacheObj, $sql, $cacheRequired);
				if ($result = call_user_func_array($callback, $params)) {
					return $result;
				}
			} else {
				if ($cacheData = $cacheObj->get($sqlEncode)) {
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
	public function saveCacheData($cacheObj, $sql, $sqlEncode, $cacheRequired, $result)
	{
		if ($this->event(self::EVENT_CACHE_QUERY_DATA)->get()) {
			$params = array($cacheObj, $sql, $result, $cacheRequired);
			$this->fire(self::EVENT_CACHE_QUERY_DATA, $params);
		} else {
			$cacheObj->set($sqlEncode, json_encode($result));
		}
	}

    /**
     * 左联接
     * @param string $class
     * @param string $alias
     * @param string $on
     * @return DatabaseModel
     * @throws Exception
     */
	public function leftJoin($class, $alias = null, $on = null)
	{
		return $this->setJoin('left', $class, $alias, $on);
	}

    /**
     * 右联接
     * @param string $class
     * @param string $alias
     * @param string $on
     * @return DatabaseModel
     * @throws Exception
     */
	public function rightJoin($class, $alias = null, $on = null)
	{
		return $this->setJoin('right', $class, $alias, $on);
	}

    /**
     * 内全联接
     * @param string $class
     * @param string $alias
     * @param string $on
     * @return DatabaseModel
     * @throws Exception
     */
	public function innerJoin($class, $alias = null, $on = null)
	{
		return $this->setJoin('inner', $class, $alias, $on);
	}

	/**
	 * 解析on参数
	 * @param string $alias
	 * @param string $on
	 * @return mixed
	 */
	public function parseJoinOnSql($alias, $on)
	{
		if (is_array($on)) {
			$on = $this->plugin()->parseCondition($on, 'AND', '=', $alias);
		}

		return $on;
	}

	/**
	 * 解析fields参数
	 * @param string $alias
	 * @param array $fields
	 * @return string
	 */
	public function parseField($alias, $fields)
	{
        $plugin = $this->plugin();
		$fieldList = explode(',', $fields);

		foreach ($fieldList as $key => $value) {
			$value = explode('.', ltrim($value));
			$field = trim($value[count($value) - 1]);
            $fieldList[$key] = $plugin->getFieldNameSql($field, $alias);
		}

		return implode(',', $fieldList);
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
	 * @param string $linkSign
	 * @return $this
	 */
	public function between($field, $value1, $value2, $alias = null, $linkSign = 'AND')
	{
		$where = array($alias, 'between', array($field, $value1, $value2), $linkSign);
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
		$this->between($field, $value1, $value2, $alias, 'OR');
		return $this;
	}

	/**
	 * 添加条件
	 * @param array $where
	 * @param string $alias
	 * @param string $linkSign
	 * @return $this
	 */
	public function where($where, $alias = null, $linkSign = 'AND')
	{
		if (!ocEmpty($where)) {
			$where = array($alias, 'where', $where, $linkSign);
			$this->sql['option']['where'][] = $where;
		}

		return $this;
	}

	/**
	 * 添加OR条件
	 * @param array|string|number $where
	 * @param string $alias
	 * @return $this
	 */
	public function orWhere($where, $alias = null)
	{
		$this->where($where, $alias, 'OR');
		return $this;
	}

	/**
	 * 生成复杂条件
	 * @param string $operator
	 * @param string $field
	 * @param mixed $value
	 * @param null $alias
	 * @return $this
	 */
	public function cWhere($operator, $field, $value, $alias = null)
	{
		$this->complexWhere('where', $operator, $field, $value, $alias);
		return $this;
	}

	/**
	 * 生成复杂条件
	 * @param string $operator
	 * @param string $field
	 * @param mixed $value
	 * @param null $alias
	 * @param string $type
	 * @return $this
	 */
	public function complexWhere($type, $operator, $field, $value, $alias = null)
	{
		$signInfo = explode('/', $operator);

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
	public function mWhere($where, $link = null)
	{
		$link = $link ? : 'AND';
		$this->sql['option']['mWhere'][] = compact('where', 'link');
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
	 * @param array $having
	 * @param string $linkSign
	 * @return $this
	 */
	public function having($having, $linkSign = 'AND')
	{
		if (!ocEmpty($having)) {
			$having = array(false, 'where', $having, $linkSign);
			$this->sql['option']['having'][] = $having;
		}

		return $this;
	}

	/**
	 * OR分组条件
	 * @param array $having
	 * @return $this
	 */
	public function orHaving($having)
	{
		$this->having($having, 'OR');
		return $this;
	}

	/**
	 * 生成复杂条件
	 * @param string $operator
	 * @param string $field
	 * @param string|int $value
	 * @return $this
	 */
	public function cHaving($operator, $field, $value)
	{
		$this->complexWhere('having', $operator, $field, $value, false);
		$this->cWhere($operator, $field, $value, false, 'having');
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
            $this->unions['option']['order'] = $orderBy;
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
		    $rows = $rows ? : 1;
		} else {
            $rows = $offset;
            $offset = 0;
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
            $rows = $rows ? : 1;
        } else {
            $rows = $offset;
            $offset = 0;
        }

        $this->unions['option']['limit'] = array($offset, $rows);
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
	 * 绑定占位符参数
	 * @param string $name
	 * @param string $value
	 * @return $this
	 */
	public function bind($name, $value)
	{
		$this->plugin()->bind($name, $value);
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
     * 设置字段别名转换映射
     * @param $tables
     * @return array
     */
	private function getAliasFields($tables)
	{
		$unJoined = count($tables) <= 1;
		$transforms = array();

		if ($unJoined) {
			$map = self::getConfig('MAPS');
			if ($map) {
				$transforms[$this->alias] = $map;
			}
		} else {
			$transforms = array();
			foreach ($tables as $alias => $row) {
				if ($alias == $this->alias) {
					if ($map = self::getConfig('MAPS')) {
						$transforms[$this->alias] = $map;
					}
				} elseif (isset($this->joins[$alias])) {
					if ($map = self::getConfig('MAPS')) {
						$transforms[$alias] = $map;
					}
				}
			}
		}

		return $transforms;
	}

	/**
	 * 是否默认字段
	 * @param $fields
	 * @return bool
	 */
    protected function isDefaultFields($fields)
	{
		$isDefault = false;
		if (empty($fields)) {
			$isDefault = true;
		} else {
			$exp = '/^\{\w+\}$/';
			if (is_array($fields) && count($fields) == 1) {
				if (isset($fields[1]) && preg_match($exp, $fields[1])) {
					$isDefault = true;
				}
			} elseif (is_string($fields)) {
				if (isset($fields) && preg_match($exp, $fields)) {
					$isDefault = true;
				}
			}
		}

		return $isDefault;
	}

    /**
     * 生成查询Sql
     * @param bool $count
     * @return mixed
     * @throws Exception
     */
    protected function genSelectSql($count = false)
	{
        $plugin = $this->plugin();
		$option = ocGet('option', $this->sql, array());
		$tables = ocGet('tables', $this->sql, array());
		$unJoined = count($tables) <= 1;
		$from = $this->getFromSql($tables, $unJoined);

		if ($count) {
			$countField = ocGet('countField', $this->sql, null);
			$isGroup = !empty($option['group']);
			$fields = $plugin->getCountSql($countField, 'total', $isGroup);
		} else {
			$aliasFields = $this->getAliasFields($tables);
			if (!isset($option['fields']) || $this->isDefaultFields($option['fields'])) {
				$option['fields'][] = array($this->alias, array_keys($this->getFields()));
			}
			$fields = $this->getFieldsSql($option['fields'], $aliasFields, $unJoined);
		}

		$option['where'] = $this->genWhere();
		if (isset($option['having'])) {
			$option['having'] = $this->getWhereSql($option['having']);
		}

		if (isset($option['limit'])) {
			if ($count) {
				ocDel($option, 'limit');
			} else {
				$option['limit'] = $plugin->getLimitSql($option['limit']);
			}
		}

		return $plugin->getSelectSql($fields, $from, $option);
	}

    /**
     * 生成条件数据
     * @return string
     * @throws Exception
     */
    protected function genWhere()
	{
        $plugin = $this->plugin();
		$option = ocGet('option', $this->sql, array());
		$where = array();

		if (!empty($option['where'])) {
			$option['where'] = $this->getWhereSql($option['where']);
			$where[] = array('where' => $option['where'], 'link' => 'AND');
		}

		if (!empty($option['mWhere'])) {
			foreach ($option['mWhere'] as $row) {
				$row['where'] = $plugin->parseCondition($row['where']);
				$where[] = $row;
			}
		}

		return $where ? $plugin->getWhereSql($where) : OC_EMPTY;
	}

    /**
     * 获取条件SQL语句
     * @param array $data
     * @return array
     * @throws Exception
     */
    protected function getWhereSql(array $data)
	{
        $plugin = $this->plugin();
		$where = array();

		foreach ($data as $key => $value) {
			list($alias, $whereType, $whereData, $linkSign) = $value;

			$condition = null;
			if ($whereType == 'where') {
				if (is_array($whereData)) {
					$whereData = $this->filterData($whereData);
				}
				if ($whereData) {
					$condition = $plugin->parseCondition($whereData, 'AND', '=', $alias);
				}
			} elseif ($whereType == 'between') {
				$field = $this->filterField($whereData[0]);
				if($field) {
					$whereData[0] = $field;
					$whereData[] = $alias;
					$condition = call_user_func_array(array($plugin, 'getBetweenSql'), $whereData);
				}
			} else {
				$condition = $this->getComplexWhere($whereData, $alias);
			}
			if ($condition) {
				$where[] = array($linkSign, $condition);
			}
		}

		$where = $plugin->linkWhere($where);
		$where = $plugin->wrapWhere($where);

		return $where;
	}

	/**
	 * 获取字段列表
	 * @param array $fieldsData
	 * @param array $aliasFields
	 * @param bool $unJoined
	 * @return mixed
	 */
    protected function getFieldsSql($fieldsData, $aliasFields, $unJoined)
	{
        $plugin = $this->plugin();

		if (is_string($fieldsData)) {
			return $fieldsData;
		}

		$fields = array();

		foreach ($fieldsData as $key => $value) {
			list($alias, $fieldData) = $value;
			if (is_string($fieldData)) {
				$fieldData = array_map('trim', (explode(',', $fieldData)));
			}
			$alias = $unJoined ? false : $alias;
			$fieldData = (array)$fieldData;
			$fields[] = $plugin->getFieldsSql($fieldData, $aliasFields, $this->alias, $alias);
		}

		$sql = $plugin->combineFieldsSql($fields, $aliasFields, $unJoined, $this->alias);
		return $sql;
	}

	/**
	 * 生成数据表SQL
	 * @param string $tables
	 * @param bool $unJoined
	 * @return null|string
	 */
    protected function getFromSql($tables, $unJoined)
	{
        $plugin = $this->plugin();
		$from = OC_EMPTY;

		foreach ($tables as $alias => $param) {
			list($type, $fullname, $on, $class, $config) = array_fill(0, 5, null);
			extract($param);

			if (empty($fullname)) continue;
			if ($unJoined) $alias = null;
			if ($config) {
				$on = $this->getJoinOnSql($alias, $config);
			}

			$on = $this->parseJoinOnSql($alias, $on);
			$fullname = $plugin->getTableFullname($fullname);
			$from = $from . $plugin->getJoinSql($type, $fullname, $alias, $on);
		}

		return $from;
	}

	/**
	 * 获取关联链接条件
	 * @param string $alias
	 * @param array $config
	 * @return string
	 */
	public function getJoinOnSql($alias, $config)
	{
        $plugin = $this->plugin();
		$joinOn = null;

		if ($config) {
			$foreignField = $plugin->getFieldNameSql($config['foreignKey'], $alias);
			$primaryField = $plugin->getFieldNameSql($config['primaryKey'], $this->alias);
			$where = array($foreignField => ocSql($primaryField));
			$condition[] = array('AND', $plugin->parseCondition($where, 'AND', null, $alias));
			if (is_array($config['condition'])) {
				foreach ($config['condition'] as $key => $value) {
					$sign = null;
					if (is_array($value)) {
						list($sign, $value) = $value;
					}
					$key = $plugin->getFieldNameSql($key, $alias);
					$where = array($key => $value);
					$condition[] = array('AND', $plugin->parseCondition($where, 'AND', $sign, $alias));
				}
			}
			$joinOn = $plugin->linkWhere($condition);
		}

		return $joinOn;
	}

    /**
     * 复杂条件
     * @param array $data
     * @param $alias
     * @return null
     */
    protected function getComplexWhere(array $data, $alias)
	{
        $plugin = $this->plugin();
		$cond = null;
		list($sign, $field, $value) = $data;

		$sign = array_map('trim', explode(OC_DIR_SEP, $sign));
		if (!$sign) {
			ocService()->error->show('fault_cond_sign');
		}

		if (isset($sign[1])) {
			list($link, $sign) = $sign;
		} else {
			$sign = $sign[0];
			$link = 'AND';
		}

		$where = array($field => $value);
		$cond = $plugin->parseCondition($where, $link, $sign, $alias);

		return $cond;
	}

	/**
	 * 获取表名
	 * @return mixed
	 */
	protected function getTable()
	{
		return $this->table;
	}

	/**
	 * 合并查询（去除重复值）
	 * @param ModelBase $model
	 * @return $this
	 */
	public function union(ModelBase $model)
	{
		$this->baseUnion($model, false);
		return $this;
	}

	/**
	 * 合并查询
	 * @param ModelBase $model
	 * @return $this
	 */
	public function unionAll(ModelBase $model)
	{
		$this->baseUnion($model, true);
		return $this;
	}

	/**
	 * 合并查询
	 * @param $model
	 * @param bool $unionAll
	 */
	public function baseUnion($model, $unionAll = false)
	{
		$this->unions['models'][] = compact('model', 'unionAll');
	}

	/**
	 * 获取合并设置
	 * @return array
	 */
	public function getUnions()
	{
		return $this->unions;
	}

    /**
     * 联接查询
     * @param $type
     * @param $class
     * @param $alias
     * @param bool $on
     * @return $this
     * @throws Exception
     */
    protected function setJoin($type, $class, $alias, $on = false)
	{
		$config = array();
		$this->connect();

		if ($type == false) {
			$alias = $this->alias;
            $fullname = $this->getTableName();
            $class = $this->tag;
		} else {
            $shardingData = array();
            $relateShardingInfo = $this->getRelateShardingInfo($class);
            if ($relateShardingInfo) {
                list($class, $shardingData) = $relateShardingInfo;
            }
			$config = $this->getRelateConfig($class);
			if ($config) {
				$alias = $alias ? : $class;
				$class = $config['class'];
			}
            $model = $class::build();
			if ($shardingData) {
                $model->sharding($shardingData);
            }
            $fullname = $model->getTableName();
			$alias = $alias ? : $fullname;
			$this->joins[$alias] = $model;
		}

		$this->sql['tables'][$alias] = compact('type', 'fullname', 'class', 'config');

		if ($config) {
			$this->addOn(null, $alias);
		} elseif ($on) {
			$this->addOn($on, $alias);
		}

		return $this;
	}

    /**
     * 通过关键字获取分库分表信息
     * @param $keyword
     * @return string
     */
	protected function getRelateShardingInfo($keyword)
    {
        $relationShardingInfo = array();

        if (preg_match('/^[\{](\w+)[\}]$/i', $keyword, $matches)) {
            $relationAlias = $matches[1];
            if (array_key_exists($relationAlias, $this->relateShardingInfo)) {
                $relationShardingInfo = $this->relateShardingInfo[$relationAlias];
            } else {
                if (array_key_exists($relationAlias, $this->relateShardingData)) {
                    $config = $this->getRelateConfig($relationAlias);
                    if ($config) {
                        $shardingData = $this->relateShardingData[$relationAlias];
                        $relationShardingInfo = array($config['class'], $shardingData);
                        $this->relateShardingInfo[$relationAlias] = $relationShardingInfo;
                    }
                }
            }
        }

        return $relationShardingInfo;
    }

    /**
     * 获取关联配置
     * @param $key
     * @return array
     */
    protected function getRelateConfig($key)
	{
        $config = $this->getConfig('RELATIONS');

		if (empty($config)) {
			return array();
		}

		if (count($config) < 3) {
			ocService()->error->show('fault_relate_config');
		}

		list($joinType, $class, $joinOn) = $config;
		$condition = isset($config[3]) ? $config[3]: null;
		$joinOn = array_map('trim', explode(',', $joinOn));

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
     * @return mixed
     */
    public static function __callStatic($name, $params)
    {
        $regExp = '/^((findRowBy)|(findBy)|(getRowBy)|(getAllBy))(\w+)$/';

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
     * @param $params
     * @return mixed
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
        $fields = $model->getFields();

        if (array_key_exists($fieldName, $fields)){
            $fieldName = ocHumpToLine($fieldName);
            if (array_key_exists($fieldName, $fields)) {
                $model->where(array($fieldName => $value));
                return $model->$method();
            }
        }

        ocService()->error->show('not_exists_find_field');
    }
}
