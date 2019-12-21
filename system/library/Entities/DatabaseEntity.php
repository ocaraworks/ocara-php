<?php
namespace Ocara\Entities;

use Ocara\Iterators\Database\EachQueryRecords;
use \ReflectionObject;
use Ocara\Core\BaseEntity;
use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

abstract class DatabaseEntity extends BaseEntity
{
    private $selected = array();
    private $oldData = array();
    private $relations = array();
    private $changes = array();

    private $insertId;
    private $source;
    private $useTransaction = true;

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
        $this->source = self::getModelClass();
        $this->setModel($this->source);

        if (method_exists($this, '__entity')) {
            $this->__entity();
        }
    }

    public function registerEvents()
    {
        parent::registerEvents();
        $this->bindEvents($this);
    }

    /**
     * 创建记录前置事件
     */
    public function beforeCreate()
    {}

    /**
     * 创建记录后置事件
     */
    public function afterCreate()
    {}

    /**
     * 更新记录前置事件
     */
    public function beforeUpdate()
    {}

    /**
     * 更新记录后置事件
     */
    public function afterUpdate()
    {}

    /**
     * 删除记录前置事件
     */
    public function beforeDelete()
    {}

    /**
     * 删除记录后置事件
     */
    public function afterDelete()
    {}

    /**
     * 获取模型类名
     * @return mixed
     */
    public static function source()
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
        if ($data) {
            $this->setProperty($data);
            if ($this->selected) {
                $this->replaceOld($data);
            }
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

        $changes = $this->changes;

        foreach ($this->oldData as $key => $value) {
            if (isset($this->$key)) {
                $dataValue = $this->$key;
                if ($value && $value != $dataValue || $value !== $dataValue) {
                    $changes[$key] = $dataValue;
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
            if (array_key_exists($key, $this->changes)) return true;
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
     * 从数据库选择记录
     * @param $values
     * @param null $options
     * @return DatabaseEntity
     */
    public static function select($values, $options = null)
    {
        $entity = new static();
        $condition = $entity->getPrimaryCondition($values);

        $data = $entity
            ->getModel()
            ->getRow($condition, $options);

        if ($data) {
            $entity->data($data);
        }

        return $entity;
    }

    /**
     * 以数据选择记录
     * @param $data
     * @return DatabaseEntity
     */
    public function dataFrom($data)
    {
        $model = static::getModelClass();
        $primaries = array_fill_keys($model::getPrimaries(), null);

        if (array_diff_key($primaries, $data)) {
            ocService()->error->show('need_primary_values');
        }

        $this->getPrimaryCondition(array_intersect_key($data, $primaries));
        $this->data($data);

        return $this;
    }

    /**
     * 获取一行对象
     * @param $condition
     * @param array $options
     * @return static
     */
    public static function selectFrom($condition, $options = [])
    {
        $options = (array)$options;

        if (isset($options['fields'])) {
            ocDel('fields', $options);
        }

        $model = static::getModelClass();
        $data = $model::build()
            ->where($condition)
            ->limit(1)
            ->getRow(null, $options);

        $entity = new static();

        if ($data) {
            $entity->data($data);
            $condition = [];
            $primaries = $model::getPrimaries();
            foreach ($primaries as $field) {
                $condition[$field] = $entity->$field;
            }
            $entity->getPrimaryCondition($condition);
        }

        return $entity;
    }

    /**
     * 新建
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function create(array $data = array())
    {
        $model = $this->getModel();

        if ($this->isUseTransaction()) {
            ocService()->transaction->begin();
        }

        $this->fire(self::EVENT_BEFORE_CREATE);

        if ($data) {
            $this->setProperty($data);
        }

        $result = $model->baseSave($this->toArray());
        $this->insertId = $model->getInsertId();
        $autoIncrementField = $this->getModel()->getAutoIncrementField();

        if ($autoIncrementField) {
            $this->$autoIncrementField = $this->insertId;
        }

        $defaultData = array_fill_keys($this->getModel()->getFieldsName(), null);
        $data = array_merge($defaultData, $this->toArray());

        $this->dataFrom($data);
        $this->fire(self::EVENT_AFTER_CREATE);

        if ($this->isUseTransaction()) {
            ocService()->transaction->commit();
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
     * 是否使用事务
     * @param $useTransaction
     */
    public function useTransaction($useTransaction)
    {
        $this->useTransaction = $useTransaction === true;
    }

    /**
     * 获取是否使用事务
     * @return bool
     */
    public function isUseTransaction()
    {
        return  $this->useTransaction || !empty($this->relations);
    }

    /**
     * 更新
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function update(array $data = array())
    {
        $result = false;
        $model = $this->getModel();

        if (empty($this->selected)) {
            ocService()->error->show('need_condition');
        }

        $data = array_merge($this->getChanged(), $data);

        if ($data) {
            if ($this->isUseTransaction()) {
                ocService()->transaction->begin();
            }
            call_user_func_array('ocDel', array(&$data, $model::getPrimaries()));
            $model->where($this->selected);

            $this->fire(self::EVENT_BEFORE_UPDATE);
            $result = $model->baseSave($data, true);
            $this->relateSave();
            $this->fire(self::EVENT_AFTER_UPDATE);

            if ($this->isUseTransaction()) {
                ocService()->transaction->commit();
            }
        }

        return $result;
    }

    /**
     * 保存
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function save(array $data = array())
    {
        if ($this->selected) {
            return $this->update($data);
        } else {
            return $this->create($data);
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
     * @return mixed
     */
    public function delete()
    {
        $model = $this->getModel();

        if (empty($this->selected)) {
            ocService()->error->show('need_condition');
        }

        if ($this->isUseTransaction()) {
            ocService()->transaction->begin();
        }

        $model->where($this->selected);
        $this->fire(self::EVENT_BEFORE_DELETE);

        $result = $model->baseDelete();
        $this->fire(self::EVENT_AFTER_DELETE);

        if ($this->isUseTransaction()) {
            ocService()->transaction->commint();
        }

        return $result;
    }

    /**
     * 主键映射
     * @param $data
     * @return array
     */
    protected static function mapPrimary($data)
    {
        $model = self::getModelClass();
        $primaries = array_fill_keys($model::getPrimaries(), null);
        $result = array_merge($primaries, array_intersect_key($data, $primaries));
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
        $model = self::getModelClass();
        $primaries = $model::getPrimaries();

        if (empty($primaries)) {
            ocService()->error->show('no_primary');
        }

        if (ocEmpty($condition)) {
            ocService()->error->show('need_primary_value');
        }

        if (is_string($condition) || is_numeric($condition)) {
            $values = explode(',', trim($condition));
            $where = array_combine($primaries, $values);
        } elseif (is_array($condition)) {
            $values = array_values($condition);
            $where = $condition;
        } else {
            ocService()->error->show('fault_primary_value_format');
        }

        if (count($primaries) == count($values)) {
            $this->selected = $where;
        } else {
            ocService()->error->show('fault_primary_num');
        }

        return $where;
    }

    /**
     * 获取模型类
     * @return mixed
     */
    public static function getModelClass()
    {
        return static::source();
    }

    /**
     * 关联查询
     * @param $alias
     * @return null|entity|EachQueryRecords
     */
    protected function relateFind($alias)
    {
        $model = $this->getModel();
        $config = $model->getRelateConfig($alias);
        $result = null;

        if ($config) {
            if (!isset($this->$config['primaryKey'])) {
                ocService()->error->show('no_relate_primary_key');
            }
            $where = array($config['foreignKey'] => $this->$config['primaryKey']);
            if (in_array($config['joinType'], array('hasOne','belongsTo'))) {
                $result = $config['class']::build()
                    ->where($where)
                    ->where($config['condition'])
                    ->selectOne();
            } elseif ($config['joinType'] == 'hasMany') {
                $result = $config['class']::build()
                    ->where($where)
                    ->where($config['condition'])
                    ->asEntity()
                    ->each();
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
                foreach ($data as $key => $entity) {
                    if ($entity->hasChanged() && is_object($entity) && $entity instanceof DatabaseEntity) {
                        $entity->$config['foreignKey'] = $this->$config['primaryKey'];
                        if ($config['condition']) {
                            foreach ($config['condition'] as $field => $value) {
                                $entity->$field = $value;
                            }
                        }
                        $entity->save();
                    }
                    $data[$key] = $entity;
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
        $config = $model->getConfig('RELATIONS');

        if (isset($config[$key])) {
            if (!isset($this->relations[$key])) {
                $this->relations[$key] = $this->relateFind($key);
            }
            return $this->relations[$key];
        }

        $result = parent::__get($key);
        return $result;
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
            $this->changes[$name] = $value;
        }
    }
}