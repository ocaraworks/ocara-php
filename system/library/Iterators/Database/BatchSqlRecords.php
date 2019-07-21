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
    protected $offset = 0;
    protected $batchLimit = 0;
    protected $totalLimit = 0;
    protected $totalPage = 0;

    protected $data = array();
    protected $sql = array();

    /**
     * 初始化
     * BatchSqlRecords constructor.
     * @param $model
     * @param $dataType
     * @param array $sql
     * @param $batchLimit
     * @param int $totalLimit
     * @param bool $debug
     */
    public function __construct($model, $dataType, array $sql, $batchLimit, $totalLimit = 0, $debug = false)
    {
        $this->model = $model;
        $this->offset = 0;
        $this->sql = $sql;
        $this->debug = $debug;

        $this->batchLimit = $batchLimit;
        $this->totalLimit = $totalLimit;

        $this->totalPage = $totalLimit > 0 ? ceil($totalLimit / $batchLimit) : 0;
        $this->dataType = $dataType ?: DriverBase::DATA_TYPE_ARRAY;

        $this->isEntity = !in_array(
            $this->dataType,
            array(DriverBase::DATA_TYPE_ARRAY, DriverBase::DATA_TYPE_OBJECT)
        );
    }

    /**
     * 重新开始
     */
    function rewind()
    {
        $this->position = 0;
        $this->getResult();
    }

    /**
     * 获取当前项
     * @return array|mixed
     */
    function current()
    {
        $data = $this->data;

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
        if ($this->totalPage > 0) {
            if ($this->position < $this->totalPage) {
                $this->position++;
                $this->getResult();
            }
        } else {
            $this->position++;
            $this->getResult();
        }
    }

    /**
     * 检测合法性
     * @return bool|void
     */
    function valid()
    {
        $this->data ? true : false;
    }

    /**
     * 获取记录结果
     * @return mixed
     */
    public function getResult()
    {
        $this->offset = $this->position * $this->batchLimit;

        $model = new $this->model();
        $model->setSql($this->sql);
        $model->limit($this->offset, $this->batchLimit);

        if (!$this->isEntity) {
            $model->setDataType($this->dataType);
        }

        $this->data = $model->getAll(null, null, $this->debug);
    }
}
