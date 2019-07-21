<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   数据库结果对象迭代器\Ocara\Iterators\Database\BatchSqlRecords
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Iterators\Database;

use \Iterator;
use Ocara\Core\DriverBase;

class BatchSqlRecords implements Iterator
{
    protected $model;
    protected $dataType;
    protected $isEntity;
    protected $debug;

    protected $position = 0;
    protected $times = 0;
    protected $offset = 0;
    protected $limitRows = 0;

    protected $data = array();
    protected $sql = array();

    /**
     * 初始化
     * BatchSqlRecords constructor.
     * @param string $model
     * @param int|string $dataType
     * @param integer $offset
     * @param integer $limitRows
     * @param array $sql
     * @param bool $debug
     */
    public function __construct($model, $dataType, $offset, $limitRows, array $sql, $debug = false)
    {
        $this->model = $model;
        $this->offset = $offset;
        $this->sql = $sql;
        $this->debug = $debug;
        $this->limitRows = $limitRows;

        $this->dataType = $dataType ?: DriverBase::DATA_TYPE_ARRAY;
        $simpleType = array(DriverBase::DATA_TYPE_ARRAY, DriverBase::DATA_TYPE_OBJECT);
        $this->isEntity = !in_array($this->dataType, $simpleType);
    }

    /**
     * 重新开始
     */
    function rewind()
    {
        $this->getResult();
        $this->position = 0;
    }

    /**
     * 获取当前项
     * @return array|mixed
     */
    function current()
    {
        $data = $this->data[$this->key()];

        if ($this->isEntity) {
            $data = new EntityRecords($data, $this->dataType);
        }

        return $data;
    }

    /**
     * 获取当前位置
     * @return int
     */
    function key()
    {
        return $this->position;
    }

    /**
     * 跳到下一个位置
     */
    function next()
    {
        if ($this->position == $this->limitRows) {
            $this->times++;
            $this->rewind();
        } else {
            $this->position++;
        }
    }

    /**
     * 检测合法性
     * @return bool
     */
    function valid()
    {
        if (!$this->data) return false;
        $position = $this->key();
        $isValid = $position < $this->limitRows && array_key_exists($position, $this->data);
        return $isValid;
    }

    /**
     * 获取记录结果
     * @return mixed
     */
    public function getResult()
    {
        $model = new $this->model();
        $model->setSql($this->sql);
        $model->limit($this->offset, $this->limitRows);

        if (!$this->isEntity) $model->asObject();

        $this->data = $model->getAll(null, null, $this->debug);
        $this->offset += $this->limitRows;
        $this->times++;
    }
}
