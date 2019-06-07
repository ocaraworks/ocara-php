<?php
namespace Ocara\Models;

use Ocara\Exceptions\Exception;
use Ocara\Iterators\Database\ObjectRecords;

defined('OC_PATH') or exit('Forbidden!');

abstract class DatabaseEntity extends DatabaseModel
{
    protected $selected = array();
    protected $changes = array();
    protected $oldData = array();
    protected $relations = array();
    protected $isOrm;

    const EVENT_BEFORE_CREATE = 'beforeCreate';
    const EVENT_AFTER_CREATE = 'afterCreate';
    const EVENT_BEFORE_UPDATE = 'beforeUpdate';
    const EVENT_AFTER_UPDATE = 'afterUpdate';
    const EVENT_BEFORE_DELETE = 'beforeDelete';
    const EVENT_AFTER_DELETE = 'afterDelete';

    /**
     * 加载数据
     * @param array $data
     * @return $this
     * @throws Exception
     */
    public function data(array $data = array())
    {
        $data = $this->_getSubmitData($data);
        if ($data) {
            $this->_setProperty($this->filterData($data));
        }

        return $this;
    }

    /**
     * 清除数据
     */
    public function clearData()
    {
        $this->selected = array();
        $this->_clearProperties($this->getFieldsName());
        return $this;
    }

    /**
     * 清除查询设置和数据
     * @return $this
     */
    public function clearAll()
    {
        parent::clearAll();
        $this->clearData();
        return $this;
    }

    /**
     * 获取模型所在目录
     * @return array|mixed
     */
    public function getModelLocation()
    {
        return '/model/entity/database/';
    }

    /**
     * 获取旧值
     * @param null $key
     * @return array|mixed
     */
    public function getOld($key = null)
    {
        if (func_num_args()) {
            if (array_key_exists($key, $this->oldData)){
                return $this->oldData[$key];
            }
            ocService()->error->show('no_old_field');
        }
        return $this->oldData;
    }

    /**
     * 获取新值
     * @param null $key
     * @return array|mixed
     */
    public function getChanged($key = null)
    {
        if (func_num_args()) {
            if (in_array($key, $this->changes)) {
                return $this->changes[$key];
            }
            ocService()->error->show('no_changed_field');
        }

        $changes = array_fill_keys($this->changes, null);
        return array_intersect_key($this->getProperty(), $changes);
    }

    /**
     * 是否赋新值
     * @param string $key
     * @return bool
     */
    public function hasChanged($key = null)
    {
        if (func_num_args()) {
            return in_array($key, $this->changes);
        }
        return !empty($this->changes);
    }

    /**
     * 是否有旧值
     * @param string $key
     * @return bool
     */
    public function hasOld($key = null)
    {
        if (func_num_args()) {
            return in_array($key, $this->oldData);
        }
        return !empty($this->oldData);
    }

    /**
     * 选择记录
     * @param $values
     * @param null $options
     * @param bool $debug
     * @return array|DatabaseModel|null
     */
    public static function select($values, $options = null, $debug = false)
    {
        $model = new static();
        $condition = $model->_getPrimaryCondition($values);

        return $model->asEntity(self::getClass())->findRow($condition, $options, $debug);
    }

    /**
     * 新建
     * @param array $data
     * @param bool $debug
     * @return bool
     * @throws Exception
     */
    public function create(array $data = array(), $debug = false)
    {
        if (!$debug && $this->relations) {
            ocService()->transaction->begin();
        }

        $this->fire(self::EVENT_BEFORE_CREATE);

        if ($data) {
            $this->_setProperty($data);
        }

        $result = parent::create($this->toArray(), $debug);

        if (!$debug) {
            $this->insertId = $this->plugin->getInsertId();
            if ($this->autoIncrementField) {
                $autoIncrementField = $this->autoIncrementField;
                $this->$autoIncrementField = $this->insertId;
            }
            $this->select($this->_mapPrimaryData($this->toArray()));
            $this->_relateSave();
            $this->fire(self::EVENT_AFTER_CREATE);
        }

        if (!$debug && $this->relations) {
            ocService()->transaction->commit();
        }

        return $result;
    }

    /**
     * 更新
     * @param array $data
     * @param bool $debug
     * @return bool
     * @throws Exception
     */
    public function update(array $data = array(), $debug = false)
    {
        if (empty($this->selected)) {
            ocService()->error->show('need_condition');
        }

        if (!$debug && $this->relations) {
            ocService()->transaction->begin();
        }

        $this->fire(self::EVENT_BEFORE_CREATE);

        if ($data){
            $oldData = array_intersect_key($this->toArray(), array_diff_key($data, $this->oldData));
            $this->oldData = array_merge($this->oldData, $oldData);
        }

        $data = array_merge($this->getChanged(), $data);
        call_user_func_array('ocDel', array(&$data, $this->primaries));
        $result = parent::update($data, $debug);

        if (!$debug) {
            $this->_relateSave();
            $this->fire(self::EVENT_AFTER_UPDATE);
        }

        if (!$debug && $this->relations) {
            ocService()->transaction->commit();
        }

        return $result;
    }

    /**
     * 保存
     * @param array $data
     * @param bool $debug
     * @return bool
     * @throws Exception
     */
    public function save(array $data = array(), $debug = false)
    {
        if ($this->selected) {
            return $this->update($data, $debug);
        } else {
            return $this->create($data, $debug);
        }
    }

    /**
     * 删除
     * @param bool $debug
     * @return bool
     * @throws Exception
     */
    public function delete($debug = false)
    {
        if (empty($this->selected)) {
            ocService()->error->show('need_condition');
        }

        $this->pushTransaction();
        $this->fire(self::EVENT_BEFORE_DELETE);

        $result = parent::delete();

        if (!$debug) {
            $this->fire(self::EVENT_AFTER_DELETE);
        }

        ocService()->transaction->commit();
        return $result;
    }

    /**
     * 赋值主键
     * @param $data
     * @return array
     */
    protected function _mapPrimaryData($data)
    {
        $result = array();
        foreach ($this->primaries as $field) {
            $result[$field] = array_key_exists($field, $data);
        }
        return $result;
    }

    /**
     * 获取主键条件
     * @param $condition
     * @return array
     * @throws Exception
     */
    protected function _getPrimaryCondition($condition)
    {
        if (empty($this->primaries)) {
            ocService()->error->show('no_primary');
        }

        if (ocEmpty($condition)) {
            ocService()->error->show('need_primary_value');
        }

        $values = array();
        if (is_string($condition) || is_numeric($condition)) {
            $values = explode(',', trim($condition));
        } elseif (is_array($condition)) {
            $values = $condition;
        } else {
            ocService()->error->show('fault_primary_value_format');
        }

        $where = array();
        if (count($this->primaries) == count($values)) {
            $where = $this->filterData(array_combine($this->primaries, $values));
        } else {
            ocService()->error->show('fault_primary_num');
        }

        return $where;
    }

    /**
     * 关联查询
     * @param $alias
     * @return null|ObjectRecords
     */
    protected function _relateFind($alias)
    {
        $config = $this->_getRelateConfig($alias);
        $result = null;

        if ($config) {
            $where = array($config['foreignKey'] => $this->$config['primaryKey']);
            if (in_array($config['joinType'], array('hasOne','belongsTo'))) {
                $result = $config['class']::build()
                    ->where($where)
                    ->where($config['condition'])
                    ->findRow();
            } elseif ($config['joinType'] == 'hasMany') {
                $result = new ObjectRecords($config['class'], array($where, $config['condition']));
                $result->setLimit(0, 0, 1);
            }
        }

        return $result;
    }

    /**
     * 关联保存
     * @return bool
     * @throws Exception
     */
    protected function _relateSave()
    {
        if (!$this->relations) {
            return true;
        }

        foreach ($this->relations as $key => $object) {
            $config = $this->_getRelateConfig($key);
            if ($config && isset($this->$config['primaryKey'])) {
                $data = array();
                if ($config['joinType'] == 'hasOne' && is_object($object)) {
                    $data = array($object);
                } elseif ($config['joinType'] == 'hasMany') {
                    if (is_object($object)) {
                        $data = array($object);
                    } elseif (is_array($object)) {
                        $data = $object;
                    }
                }
                foreach ($data as &$entity) {
                    if ($entity->hasChanged() && is_object($entity) && $entity instanceof DatabaseEntity) {
                        $entity->$config['foreignKey'] = $this->$config['primaryKey'];
                        if ($config['condition']) {
                            foreach ($config['condition'] as $field => $value) {
                                $entity->$field = $value;
                            }
                        }
                        $entity->save();
                    }
                }
            }
        }

        return true;
    }

    /**
     * 获取无法访问的属性
     * @param string $key
     * @return mixed
     */
    public function &__get($key)
    {
        if (isset(self::$_config[$this->tag]['RELATIONS'][$key])) {
            if (!isset($this->relations[$key])) {
                $this->relations[$key] = $this->_relateFind($key);
            }
            return $this->relations[$key];
        }

        return parent::__get($key);
    }

    /**
     * 设置无法访问的属性
     * @param $name
     * @param $value
     * @return bool|void
     * @throws Exception
     */
    public function __set($name, $value)
    {
        if (isset(self::$_config[$this->tag]['RELATIONS'][$name])) {
            $this->relations[$name] = $value;
        } else {
            $oldValue = null;
            if ($this->selected) {
                if (!array_key_exists($name, $this->oldData)){
                    $oldValue = $this->$name;
                }
            }
            parent::__set($name, $value);
            if ($this->selected && isset($this->$name)) {
                $this->changes[] = $name;
                $this->oldData[$name] = $oldValue;
            }
        }
    }
}