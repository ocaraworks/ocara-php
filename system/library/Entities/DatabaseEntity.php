<?php
namespace Ocara\Entities;

use \ReflectionObject;
use Ocara\Core\BaseEntity;
use Ocara\Exceptions\Exception;
use Ocara\Iterators\Database\ObjectRecords;

defined('OC_PATH') or exit('Forbidden!');

abstract class DatabaseEntity extends BaseEntity
{
    private $selected = array();
    private $oldData = array();
    private $relations = array();

    /**
     * @var int $insertId
     */
    private $insertId;

    /**
     * @var string $source
     */
    private $source;

    const EVENT_BEFORE_CREATE = 'beforeCreate';
    const EVENT_AFTER_CREATE = 'afterCreate';
    const EVENT_BEFORE_UPDATE = 'beforeUpdate';
    const EVENT_AFTER_UPDATE = 'afterUpdate';
    const EVENT_BEFORE_DELETE = 'beforeDelete';
    const EVENT_AFTER_DELETE = 'afterDelete';

    /**
     * DatabaseEntity constructor.
     */
    public function __construct()
    {
        $this->source = $this->source();
        $this->setModel($this->source);

        if (method_exists($this, '__entity')) {
            $this->__entity();
        }
    }

    /**
     * 获取模型类名
     * @return mixed
     */
    public function source()
    {}

    /**
     * 获取模型类名
     * @return mixed
     */
    public function getModel()
    {
       return $this->plugin();
    }

    /**
     * 修改数据来源
     * @param $model
     * @return mixed
     */
    public function setModel($model)
    {
        if ($model) {
            if (is_string($model)) {
                $this->source = $model;
                return $this->setPlugin(new $model());
            } elseif (is_object($model)) {
                $reflection = new ReflectionObject($model);
                $this->source = $reflection->getName();
                return $this->setPlugin($model);
            }
        }

        ocService()->error->show('invalid_entity_database');
    }

    /**
     * 加载数据
     * @param array $data
     * @return $this
     */
    public function data(array $data)
    {
        $model = $this->getModel();

        if ($data) {
            $data = $model->filterData($data);
            $this->setProperty($data);
        }

        return $this;
    }

    /**
     * 清除数据
     */
    public function clear()
    {
        $this->selected = array();
        $this->oldData = array();
        $fields = $this->getModel()->getFieldsName();
        $this->clearProperties($fields);
        return $this;
    }

    /**
     * 清理属性
     * @param array $fields
     */
    protected function clearProperties(array $fields = array())
    {
        $fields = $fields ? : array_keys($this->toArray());

        foreach ($fields as $field) {
            if (isset($this->$field)) {
                $this->$field = null;
                unset($this->$field);
            }
        }
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
            return null;
        }
        return $this->oldData;
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
     * 获取新值
     * @param null $key
     * @return array|mixed
     */
    public function getChanged($key = null)
    {
        if (func_num_args()) {
            return $this->hasChanged($key) ? $this->$key : null;
        }

        $data = $this->toArray();
        $changes = array();

        foreach ($this->oldData as $key => $value) {
            if (isset($this->$key)) {
                $dataValue = $data[$key];
                if ($value && $value != $dataValue || $value !== $dataValue) {
                    $changes[$key] = $data[$key];
                }
            }
        }

        return $changes;
    }

    /**
     * 是否赋新值
     * @param string $key
     * @return bool
     */
    public function hasChanged($key = null)
    {
        if (func_num_args()) {
            if (array_key_exists($key, $this->oldData)) {
                $value = $this->oldData[$key];
                if (isset($this->$key)) {
                    $dataValue = $this->$key;
                    return $value && $value != $dataValue || $value !== $dataValue;
                }
            }
            return false;
        }

        $changes = $this->getChanged();
        return !empty($changes);
    }

    /**
     * 选择记录
     * @param $values
     * @param null $options
     * @param bool $debug
     * @return DatabaseEntity
     */
    public static function select($values, $options = null, $debug = false)
    {
        $entity = new static();
        $condition = $entity->getPrimaryCondition($values);

        $data = $entity
            ->getModel()
            ->getRow($condition, $options, $debug);

        $entity->data($data);
        $entity->replaceOld($data);

        return $entity;
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
        $model = $this->getModel();

        if (!$debug && $this->relations) {
            ocService()->transaction->begin();
        }

        $this->fire(self::EVENT_BEFORE_CREATE);

        if ($data) {
            $this->setProperty($data);
        }

        $result = $model->create($this->toArray(), $debug);

        if (!$debug) {
            $this->insertId = $model->getInsertId();
            if ($this->getAutoIncrementField()) {
                $autoIncrementField = $this->getAutoIncrementField();
                $this->$autoIncrementField = $this->insertId;
            }
            $this->select($this->mapPrimaryData($this->toArray()));
            $this->relateSave();
            $this->fire(self::EVENT_AFTER_CREATE);
        }

        return $result;
    }

    /**
     * 获取最后插入的记录ID
     * @return mixed
     */
    public function getInsertId()
    {
        return $this->insertId;
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
        $model = $this->getModel();

        if (empty($this->selected)) {
            ocService()->error->show('need_condition');
        }

        $model->where($this->selected);

        if (!$debug && $this->relations) {
            ocService()->transaction->begin();
        }

        $this->fire(self::EVENT_BEFORE_CREATE);

        $data = array_merge($this->getChanged(), $data);
        call_user_func_array('ocDel', array(&$data, $this->getPrimaries()));
        $result = $model->update($data, $debug);

        if (!$debug) {
            $this->relateSave();
            $this->fire(self::EVENT_AFTER_UPDATE);
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
     * 保存旧值
     * @param $key
     * @param null $value
     * @return $this
     */
    public function replaceOld($key, $value = null)
    {
        if (is_array($key)) {
            $this->oldData = $key;
        } else {
            $this->oldData[$key] = $value;
        }
        return $this;
    }

    /**
     * 删除
     * @param bool $debug
     * @return mixed
     */
    public function delete($debug = false)
    {
        $model = $this->getModel();

        if (empty($this->selected)) {
            ocService()->error->show('need_condition');
        }

        $model->where($this->selected);
        $this->fire(self::EVENT_BEFORE_DELETE);

        $result = $model->delete();
        if (!$debug) {
            $this->fire(self::EVENT_AFTER_DELETE);
        }

        return $result;
    }

    /**
     * 赋值主键
     * @param $data
     * @return array
     */
    protected function mapPrimaryData($data)
    {
        $plugin = $this->plugin();
        $result = array();

        foreach ($plugin->getPrimaries() as $field) {
            $result[$field] = array_key_exists($field, $data);
        }

        return $result;
    }

    /**
     * 获取主键条件
     * @param $condition
     * @return array
     */
    protected function getPrimaryCondition($condition)
    {
        $where = array();
        $values = array();
        $model = $this->getModel();
        $primaries = $model->getPrimaries();

        if (empty($primaries)) {
            ocService()->error->show('no_primary');
        }

        if (ocEmpty($condition)) {
            ocService()->error->show('need_primary_value');
        }

        if (is_string($condition) || is_numeric($condition)) {
            $values = explode(',', trim($condition));
        } elseif (is_array($condition)) {
            $values = $condition;
        } else {
            ocService()->error->show('fault_primary_value_format');
        }

        if (count($primaries) == count($values)) {
            $where = $model->filterData(array_combine($primaries, $values));
            $this->selected = $where;
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
    protected function relateFind($alias)
    {
        $model = $this->getModel();
        $config = $model->getRelateConfig($alias);
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
    protected function relateSave()
    {
        if (!$this->relations) return true;

        $model = $this->getModel();

        foreach ($this->relations as $key => $object) {
            $config = $model->getRelateConfig($key);
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
        $model = $this->getModel();
        $relations = $model->getConfig('RELATIONS');

        if (isset($relations[$key])) {
            if (!isset($this->relations[$key])) {
                $this->relations[$key] = $this->relateFind($key);
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
        $model = $this->getModel();
        $relations = $model->getConfig('RELATIONS');

        if (isset($relations[$name])) {
            $this->relations[$name] = $value;
        } else {
            parent::__set($name, $value);
        }
    }
}