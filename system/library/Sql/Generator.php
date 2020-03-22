<?php
/**
 * Sql生成器类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Sql;

use Ocara\Core\Base;
use Ocara\Core\DatabaseBase;

class Generator extends Base
{
    protected $database;
    protected $sql;
    protected $alias;
    protected $databaseName;
    protected $tableName;
    protected $maps = array();
    protected $joins = array();
    protected $fields = array();
    protected $primaries = array();

    /**
     * Sql constructor.
     * @param DatabaseBase $database
     */
    public function __construct(DatabaseBase $database)
    {
        $this->database = $database;
        $databaseType = $database->getType();
        $databaseConfig = $database->getConfig();

        $plugin = SqlFactory::create($databaseType, $database);
        $plugin->setConfig($databaseConfig);
        $this->setPlugin($plugin);
    }

    /**
     * @param $sql
     */
    public function setSql($sql)
    {
        $this->sql = $sql;
    }

    /**
     * @param $maps
     */
    public function setMaps($maps)
    {
        $this->maps = $maps;
    }

    /**
     * @param $joins
     */
    public function setJoins($joins)
    {
        $this->joins = $joins;
    }

    /**
     * @param $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    /**
     * @param $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    /**
     * 设置数据库名称
     * @param $databaseName
     */
    public function setDatabaseName($databaseName)
    {
        $this->databaseName = $databaseName;
    }

    /**
     * 设置表名称
     * @param $tableName
     */
    public function setTable($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * 设置主键
     * @param $primaries
     */
    public function setPrimaries($primaries)
    {
        $this->primaries = $primaries;
    }

    /**
     * 销毁变量
     */
    public function __destruct()
    {
        $this->database = null;
        unset($this->database);
    }

    /**
     * 获取SELECT查询语句
     * @param bool $count
     * @param array $unions
     * @param null $isFilterCondition
     * @return array
     */
    public function genSelectSql($count = false, array $unions = array(), $isFilterCondition = null)
    {
        $plugin = $this->plugin();

        $option = !empty($this->sql['option']) ? $this->sql['option'] : array();
        $tables = !empty($this->sql['tables']) ? $this->sql['tables'] : array();
        $unJoined = count($tables) <= 1;

        $from = $this->getFromSql($tables, $unJoined);

        if ($count && empty($unions['models'])) {
            $countField = !empty($this->sql['countField']) ? $this->sql['countField'] : null;
            $isGroup = !empty($option['group']);
            $fields = $plugin->getCountSql($countField, 'total', $isGroup);
        } else {
            $aliasFields = $this->getAliasFields($tables, $this->alias);
            if (!isset($option['fields']) || $this->isDefaultFields($option['fields'])) {
                $option['fields'][] = array($this->alias, array_keys($this->fields));
            }
            $fields = $this->getFieldsSql($option['fields'], $aliasFields, $unJoined);
        }

        $option['where'] = $this->genWhereSql($isFilterCondition);
        if (isset($option['having'])) {
            $option['having'] = $this->getConditionSql($option['having'], $isFilterCondition);
        }

        if (isset($option['limit'])) {
            if ($count) {
                ocDel($option, 'limit');
            } else {
                $option['limit'] = $plugin->getLimitSql($option['limit']);
            }
        }

        $sqlData = $plugin->getSelectSql($fields, $from, $option);

        if (!empty($unions['models'])) {
            $sqlData = $this->getUnionSql($sqlData, $unions, $count);
        }

        return $sqlData;
    }

    /**
     * 获取条件SQL语句
     * @param array $data
     * @param $isFilterCondition
     * @return array
     */
    public function getConditionSql(array $data, $isFilterCondition = null)
    {
        $plugin = $this->plugin();
        $where = array();

        foreach ($data as $key => $value) {
            list($alias, $whereType, $whereData, $linkSign) = $value;

            $condition = null;
            if ($whereType == 'where') {
                if (is_array($whereData)) {
                    if ($isFilterCondition) {
                        $whereData = $this->filterData($whereData);
                    }
                    $whereData = $this->formatFields($whereData, false);
                }
                if ($whereData) {
                    $condition = $plugin->parseCondition($whereData, 'AND', '=', $alias);
                }
            } elseif ($whereType == 'between') {
                $field = $this->filterField($whereData[0]);
                if ($field) {
                    $whereData[0] = $field;
                    $whereData[] = $alias;
                    $condition = call_user_func_array(array($this->database, 'getBetweenSql'), $whereData);
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
     * 复杂条件
     * @param array $data
     * @param $alias
     * @return array|bool|mixed|string|null
     */
    public function getComplexWhere(array $data, $alias)
    {
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
        $cond = $this->plugin()->parseCondition($where, $link, $sign, $alias);

        return $cond;
    }

    /**
     * 别名字段数据映射过滤
     * @param array $data
     * @return array
     */
    public function filterData(array $data)
    {
        $result = array();

        foreach ($data as $field => $value) {
            if (!is_object($value)) {
                $key = $this->filterField($field);
                if ($key) {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * 格式化数据
     * @param $data
     * @param $isCondition
     * @return mixed
     */
    public function formatFields($data, $isCondition = false)
    {
        if ($this->fields) {
            $data = $this->database->formatFieldValues($this->fields, $data, $isCondition);
        }
        return $data;
    }

    /**
     * 字段别名映射过滤
     * @param $field
     * @return null
     */
    public function filterField($field)
    {
        $key = isset($this->maps[$field]) ? $this->maps[$field] : $field;

        if (!$this->plugin()->hasAlias($key)) {
            if (!isset($this->fields[$key])) {
                return null;
            }
        }

        return $key;
    }

    /**
     * 生成条件数据
     * @param $isFilterCondition
     * @return string
     */
    public function genWhereSql($isFilterCondition = null)
    {
        $option = !empty($this->sql['option']) ? $this->sql['option'] : array();
        $where = array();

        if (!empty($option['where'])) {
            $option['where'] = $this->getConditionSql($option['where'], $isFilterCondition);
            $where[] = array('where' => $option['where'], 'link' => 'AND');
        }

        if (!empty($option['moreWhere'])) {
            foreach ($option['moreWhere'] as $row) {
                $row['where'] = $this->plugin()->parseCondition($row['where']);
                $where[] = $row;
            }
        }

        return $where ? $this->plugin()->getConditionSql($where) : OC_EMPTY;
    }

    /**
     * 获取字段列表
     * @param array $fieldsData
     * @param array $aliasFields
     * @param bool $unJoined
     * @return mixed
     */
    public function getFieldsSql($fieldsData, $aliasFields, $unJoined)
    {
        $fields = array();

        if (is_string($fieldsData)) return $fieldsData;

        foreach ($fieldsData as $key => $value) {
            list($alias, $fieldData) = $value;
            if (is_string($fieldData)) {
                $fieldData = array_map('trim', (explode(',', $fieldData)));
            }
            $alias = $unJoined ? null : $alias;
            $fieldData = (array)$fieldData;
            $fields[] = $this->plugin()->getFieldsSql($fieldData, $aliasFields, $this->alias, $alias);
        }

        $sql = $this->plugin()->combineFieldsSql($fields, $aliasFields, $unJoined, $this->alias);
        return $sql;
    }

    /**
     * 设置字段别名转换映射
     * @param $tables
     * @param $currentAlias
     * @return array
     */
    public function getAliasFields($tables, $currentAlias)
    {
        $unJoined = count($tables) <= 1;
        $transforms = array();

        if ($unJoined) {
            if ($this->maps) {
                $transforms[$currentAlias] = $this->maps;
            }
        } else {
            $transforms = array();
            foreach ($tables as $alias => $row) {
                if ($alias == $currentAlias) {
                    if ($this->maps) {
                        $transforms[$currentAlias] = $this->maps;
                    }
                } elseif (isset($this->joins[$alias])) {
                    if ($this->maps) {
                        $transforms[$alias] = $this->maps;
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
    public function isDefaultFields($fields)
    {
        $isDefault = false;

        if ($fields) {
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
        } else {
            $isDefault = true;
        }

        return $isDefault;
    }

    /**
     * 生成数据表SQL
     * @param $tables
     * @param $unJoined
     * @return string
     */
    public function getFromSql($tables, $unJoined)
    {
        $from = OC_EMPTY;
        $shardingConfig = !empty($this->sql['sharding']['relate']) ? $this->sql['sharding']['relate'] : array();

        foreach ($tables as $alias => $param) {
            if (empty($param['fullname'])) continue;

            if (empty($param['type'])) {
                $param['fullname'] = $this->tableName;
                $database = $this->databaseName;
            } else {
                $model = $param['class']::build();
                $shardingData = !empty($shardingConfig[$alias]) ? $shardingConfig[$alias] : array();
                if (!$shardingData) {
                    $shardingData = !empty($shardingConfig[$param['class']]) ? $shardingConfig[$param['class']] : array();
                }
                if ($shardingData) {
                    $model->sharding($shardingData);
                }
                $param['fullname'] = $model->getTableName();
                $database = $model->getDatabaseName();
            }

            if ($unJoined) {
                $alias = null;
            }

            if (!empty($param['on'])) {
                $param['on'] = $this->parseJoinOnSql($alias, $param['on']);
            } elseif ($param['config']) {
                $param['on'] = $this->getJoinOnSql($alias, $param['config']);
            } else {
                $param['on'] = OC_EMPTY;
            }

            $param['fullname'] = $this->plugin()->getTableFullname($param['fullname'], $database);
            $from = $from . $this->plugin()->getJoinSql($param['type'], $param['fullname'], $alias, $param['on']);
        }

        return $from;
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
     * 获取Union语句
     * @param $sqlData
     * @param $unions
     * @param $count
     * @return array
     */
    public function getUnionSql($sqlData, $unions, $count)
    {
        list($sql, $params) = $sqlData;
        $plugin = $this->plugin();

        if (!empty($unions['models'])) {
            $sql = $plugin->wrapSql($sql);
            foreach ($unions['models'] as $union) {
                $executeOptions = array('close_union' => true);
                $union['model']->debug()->getAll(null, null, $executeOptions);
                $lastSql = $union['model']->getLastSql();
                list($unionSql, $unionParams) = reset($lastSql);
                $sql .= $plugin->getUnionSql($unionSql, $union['unionAll']);
                $params = array_merge($params, $unionParams);
            }
            if ($count) {
                $sql = $plugin->getSubQuerySql($sql, $plugin->getCountSql());
            } elseif (!empty($unions['option'])) {
                $option = array(
                    'orderBy' => isset($unions['option']['order']) ? $unions['option']['order'] : null,
                    'limit' => isset($unions['option']['limit']) ? $unions['option']['limit'] : array()
                );
                $sql = $plugin->getSubQuerySql($sql, $option);
            }
        }

        return array($sql, $params);
    }

    /**
     * 获取关联链接条件
     * @param $alias
     * @param $config
     * @return null
     */
    public function getJoinOnSql($alias, $config)
    {
        $joinOn = null;

        if ($config) {
            $foreignField = $this->plugin()->getFieldNameSql($config['foreignKey'], $alias);
            $primaryField = $this->plugin()->getFieldNameSql($config['primaryKey'], $this->alias);
            $where = array($foreignField => ocSql($primaryField));
            $condition[] = array('AND', $this->plugin()->parseCondition($where, 'AND', null, $alias));
            if (is_array($config['condition'])) {
                foreach ($config['condition'] as $key => $value) {
                    $sign = null;
                    if (is_array($value)) {
                        list($sign, $value) = $value;
                    }
                    $key = $this->plugin()->getFieldNameSql($key, $alias);
                    $where = array($key => $value);
                    $condition[] = array('AND', $this->plugin()->parseCondition($where, 'AND', $sign, $alias));
                }
            }
            $joinOn = $this->plugin()->linkWhere($condition);
        }

        return $joinOn;
    }

    /**
     * 获取Insert语句
     * @param $table
     * @param $data
     * @param $isFilterData
     * @return mixed
     */
    public function getInsertSql($table, $data, $isFilterData)
    {
        if ($isFilterData) {
            $data = $this->filterData($data);
        }
        $data = $this->formatFields($data);
        $tableName = $this->getTableFullname($table, $this->databaseName);
        return $this->plugin()->getInsertSql($tableName, $data);
    }

    /**
     * 获取Update语句
     * @param $table
     * @param $data
     * @param $where
     * @param $isFilterData
     * @return mixed
     */
    public function getUpdateSql($table, $data, $where, $isFilterData)
    {
        if ($isFilterData) {
            $data = $this->filterData($data);
        }
        $data = $this->stripPrimaries($data);
        $data = $this->formatFields($data);
        $tableName = $this->getTableFullname($table, $this->databaseName);
        return $this->plugin()->getUpdateSql($tableName, $data, $where);
    }

    /**
     * 过滤掉主键
     * @param array $data
     * @return array
     */
    public function stripPrimaries(array $data)
    {
        call_user_func_array('ocDel', array(&$data, $this->primaries));
        return $data;
    }

    /**
     * 获取Replace语句
     * @param $table
     * @param $data
     * @param $isFilterData
     * @return mixed
     */
    public function getReplaceSql($table, $data, $isFilterData)
    {
        if ($isFilterData) {
            $data = $this->filterData($data);
        }
        $data = $this->formatFields($data);
        $tableName = $this->getTableFullname($table, $this->databaseName);
        return $this->plugin()->getReplaceSql($tableName, $data);
    }
}