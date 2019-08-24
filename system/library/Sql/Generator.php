<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Sql生成器类Generator
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Sql;

use Ocara\Core\Base;
use Ocara\Core\DatabaseBase;
use Ocara\Sql\Databases\SqlFactory;

class Generator extends Base
{
    protected $database;
    protected $sql;
    protected $alias;
    protected $databaseName;
    protected $maps;
    protected $joins;
    protected $fields;
    protected $databaseSql;

    /**
     * Sql constructor.
     * @param DatabaseBase $database
     * @param $databaseName
     */
    public function __construct(DatabaseBase $database, $databaseName)
    {
        $databaseType = $database->getType();
        $databaseConfig = $database->getConfig();

        $plugin = SqlFactory::create($databaseType);
        $plugin->setConfig($databaseConfig);
        $this->setPlugin($plugin);

        $this->database = $database;
        $this->databaseName = $databaseName;
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
     * @return mixed
     */
    public function genSelectSql($count = false, array $unions = array())
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
            $aliasFields = $this->getAliasFields($tables, $this->alias);
            if (!isset($option['fields']) || $this->isDefaultFields($option['fields'])) {
                $option['fields'][] = array($this->alias, array_keys($this->fields));
            }
            $fields = $this->getFieldsSql($option['fields'], $aliasFields, $unJoined);
        }

        $option['where'] = $this->genWhereSql();
        if (isset($option['having'])) {
            $option['having'] = $this->getConditionSql($option['having']);
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
            $sqlData = $this->getUnionSql($sqlData, $count);
        }

        return $sqlData;
    }

    /**
     * 获取条件SQL语句
     * @param array $data
     * @return array|string
     */
    public function getConditionSql(array $data)
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

        if ($this->fields) {
            $result = $this->plugin()->formatFieldValues($this->fields, $result);
        }

        return $result;
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
     * @return bool|string
     */
    public function genWhereSql()
    {
        $option = ocGet('option', $this->sql, array());
        $where = array();

        if (!empty($option['where'])) {
            $option['where'] = $this->getConditionSql($option['where']);
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
            $alias = $unJoined ? false : $alias;
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

        foreach ($tables as $alias => $param) {
            if (empty($param['fullName'])) continue;

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

            $param['fullName'] = $this->plugin()->getTableFullname($param['fullName'], $this->databaseName);
            $from = $from . $this->plugin()->getJoinSql($param['type'], $param['fullName'], $alias, $param['on']);
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
     * 攻取Union语句
     * @param $sqlData
     * @param $count
     * @return array
     */
    public function getUnionSql($sqlData, $count)
    {
        list($sql, $params) = $sqlData;
        $plugin = $this->plugin();

        if (!empty($unions['models'])) {
            $sql = $plugin->wrapSql($sql);
            foreach ($unions['models'] as $union) {
                if ($count) {
                    $unionData = $union['model']->getTotal();
                } else {
                    $unionData = $union['model']->getAll();
                }
                list($unionSql, $unionParams) = $unionData;
                $sql .= $plugin->getUnionSql($unionSql, $union['unionAll']);
                $params = array_merge($params, $unionParams);
            }
            if (!$count && !empty($unions['option'])) {
                $orderBy = $unions['option']['order'];
                $limit = $unions['option']['limit'];
                $sql = $plugin->getSubQuerySql($sql, $orderBy, $limit);
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
}