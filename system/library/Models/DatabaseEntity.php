<?php
namespace Ocara\Models;

use Ocara\Exceptions\Exception;
use Ocara\Iterators\Database\ObjectRecords;

defined('OC_PATH') or exit('Forbidden!');

abstract class DatabaseEntity extends DatabaseModel
{
    protected $_selected = array();
    protected $_changes = array();
    protected $_oldData = array();
    protected $_relations = array();
    protected $_isOrm;

    const EVENT_BEFORE_CREATE = 'beforeCreate';
    const EVENT_AFTER_CREATE = 'afterCreate';
    const EVENT_BEFORE_UPDATE = 'beforeUpdate';
    const EVENT_AFTER_UPDATE = 'afterUpdate';
    const EVENT_BEFORE_DELETE = 'beforeDelete';
    const EVENT_AFTER_DELETE = 'afterDelete';

    /**
     * 新建ORM模型
     * @param array $data
     * @return $this
     * @throws Exception
     */
    public function data(array $data = array())
    {
        $data = $this->_getSubmitData($data);
        if ($data) {
            $this->_setProperty($this->map($data));
        }

        $this->_isOrm = true;
        return $this;
    }

    /**
     * 清理ORM数据
     */
    public function clearData()
    {
        $this->_selected = array();
        $this->_isOrm = false;

        $fields = $this->getFields();
        foreach ($fields as $field) {
            if (isset($this->$field)) {
                $this->$field = null;
            }
        }

        $this->_clearProperties();
        return $this;
    }

    /**
     * 清理Model的SQL和ORM数据
     * @return $this
     */
    public function clearAll()
    {
        parent::clearAll();
        $this->clearData();
        return $this;
    }

    /**
     * 是否是ORM模型
     */
    public function isOrm()
    {
        return $this->_isOrm;
    }

    /**
     * 获取旧值
     * @param null $key
     * @return array|mixed
     */
    public function getOld($key = null)
    {
        if (func_num_args()) {
            if (array_key_exists($key, $this->_oldData)){
                return $this->_oldData[$key];
            }
            ocService()->error->show('no_old_field');
        }
        return $this->_oldData;
    }

    /**
     * 获取已修改字段数据
     * @param null $key
     * @return array|mixed
     */
    public function getChanged($key = null)
    {
        if (func_num_args()) {
            if (in_array($key, $this->_changes)) {
                return $this->_changes[$key];
            }
            ocService()->error->show('no_changed_field');
        }

        $changes = array_fill_keys($this->_changes, null);
        return array_intersect_key($this->getProperty(), $changes);
    }

    /**
     * 是否有改变某个字段
     * @param string $key
     * @return bool
     */
    public function hasChanged($key = null)
    {
        if (func_num_args()) {
            return in_array($key, $this->_changes);
        }
        return !empty($this->_changes);
    }

    /**
     * 是否有改变某个字段
     * @param string $key
     * @return bool
     */
    public function hasOld($key = null)
    {
        if (func_num_args()) {
            return in_array($key, $this->_oldData);
        }
        return !empty($this->_oldData);
    }

    /**
     * 按主键选择一行记录
     * @param $values
     * @param null $options
     * @param bool $debug
     * @return array|DatabaseModel|null
     */
    public static function select($values, $options = null, $debug = false)
    {
        $model = new static();
        $condition = $model->_getPrimaryCondition($values);

        return $model->asEntity(__CLASS__)->findRow($condition, $options, $debug);
    }

    /**
     * 新建记录
     * @param array $data
     * @param bool $debug
     * @return bool
     * @throws Exception
     */
    public function create(array $data = array(), $debug = false)
    {
        if (!$debug && $this->_relations) {
            ocService()->transaction->begin();
        }

        $this->fire(self::EVENT_BEFORE_CREATE);

        if ($data) {
            $this->_setProperty($data);
        }

        $result = parent::create($this->toArray(), $debug);

        if (!$debug) {
            $this->_insertId = $this->_plugin->getInsertId();
            if ($this->_autoIncrementField) {
                $autoIncrementField = $this->_autoIncrementField;
                $this->$autoIncrementField = $this->_insertId;
            }
            $this->select($this->_mapPrimaryData($this->toArray()));
            $this->_relateSave();
            $this->fire(self::EVENT_AFTER_CREATE);
        }

        if (!$debug && $this->_relations) {
            ocService()->transaction->commit();
        }

        return $result;
    }

    /**
     * 更新记录
     * @param array $data
     * @param bool $debug
     * @return bool
     * @throws Exception
     */
    public function update(array $data = array(), $debug = false)
    {
        if (empty($this->_selected)) {
            ocService()->error->show('need_condition');
        }

        if (!$debug && $this->_relations) {
            ocService()->transaction->begin();
        }

        $this->fire(self::EVENT_BEFORE_CREATE);

        if ($data){
            $oldData = array_intersect_key($this->toArray(), array_diff_key($data, $this->_oldData));
            $this->_oldData = array_merge($this->_oldData, $oldData);
        }

        $data = array_merge($this->getChanged(), $data);
        call_user_func_array('ocDel', array(&$data, $this->_primaries));
        $result = parent::update($data, $debug);

        if (!$debug) {
            $this->_relateSave();
            $this->fire(self::EVENT_AFTER_UPDATE);
        }

        if (!$debug && $this->_relations) {
            ocService()->transaction->commit();
        }

        return $result;
    }

    /**
     * 保存数据（ORM模型）
     * @param array $data
     * @param bool $debug
     * @return bool
     * @throws Exception
     */
    public function save(array $data = array(), $debug = false)
    {
        if ($this->_selected) {
            return $this->update($data, $debug);
        } else {
            return $this->create($data, $debug);
        }
    }

    /**
     * 删除记录
     * @param bool $debug
     * @return bool
     * @throws Exception
     */
    public function delete($debug = false)
    {
        if (empty($this->_selected)) {
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
     * 过滤主键字段
     * @param $data
     * @return array
     */
    protected function _mapPrimaryData($data)
    {
        $result = array();
        foreach ($this->_primaries as $field) {
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
        if (empty($this->_primaries)) {
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
        if (count($this->_primaries) == count($values)) {
            $where = $this->map(array_combine($this->_primaries, $values));
        } else {
            ocService()->error->show('fault_primary_num');
        }

        return $where;
    }

    /**
     * 关联模型查询
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
     * 关联模型数据保存
     * @return bool
     * @throws Exception
     */
    protected function _relateSave()
    {
        if (!$this->_relations) {
            return true;
        }

        foreach ($this->_relations as $key => $object) {
            $config = $this->_getRelateConfig($key);
            if ($config && $this->hasProperty($config['primaryKey'])) {
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
     * 获取关联模型
     * @param string $key
     * @return mixed
     */
    public function &__get($key)
    {
        if (isset(self::$_config[$this->_tag]['JOIN'][$key])) {
            if (!isset($this->_relations[$key])) {
                $this->_relations[$key] = $this->_relateFind($key);
            }
            return $this->_relations[$key];
        }

        return parent::__get($key);
    }

    /**
     * 设置未定义的属性
     * @param string $name
     * @param mxied $value
     * @return mixed|void
     */
    public function __set($name, $value)
    {
        if (isset(self::$_config[$this->_tag]['JOIN'][$name])) {
            $this->_relations[$name] = $value;
        } else {
            $oldValue = null;
            if ($this->_selected) {
                if (!array_key_exists($name, $this->_oldData)){
                    $oldValue = $this->getProperty($name);
                }
            }
            parent::__set($name, $value);
            if ($this->_selected && $this->hasProperty($name)) {
                $this->_changes[] = $name;
                $this->_oldData[$name] = $oldValue;
            }
        }
    }
}