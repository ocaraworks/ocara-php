<?php
/**
 * Ocara开源框架 数据库批量查询结果对象迭代器
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Iterators\Database;

use \Iterator;
use Ocara\Core\DriverBase;

class BatchQueryRecords implements Iterator
{
    protected $model;
    protected $dataType;
    protected $isEntity;

    protected $position = 0;
    protected $offset = 0;
    protected $batchLimit = 0;
    protected $totalLimit = 0;
    protected $totalPage = 0;

    protected $data = array();

    /**
     * 初始化
     * BatchQueryRecords constructor.
     * @param $model
     * @param $batchLimit
     * @param int $totalLimit
     */
    public function __construct($model, $batchLimit, $totalLimit = 0)
    {
        $this->model = $model;
        $this->offset = 0;

        $this->batchLimit = $batchLimit;
        $this->totalLimit = $totalLimit;

        $this->totalPage = $totalLimit > 0 ? ceil($totalLimit / $batchLimit) : 0;
        $this->dataType = $model->getDataType() ?: DriverBase::DATA_TYPE_ARRAY;

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
            $data = new EntityRecords($data, $this->dataType, $this->model->getSharding());
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
        return $this->data ? true : false;
    }

    /**
     * 获取记录结果
     * @return mixed
     */
    public function getResult()
    {
        $tempDataType = DriverBase::DATA_TYPE_ARRAY;
        $this->offset = $this->position * $this->batchLimit;

        if ($this->dataType == DriverBase::DATA_TYPE_OBJECT) {
            $tempDataType = DriverBase::DATA_TYPE_OBJECT;
        }

        $this->model
            ->setDataType($tempDataType)
            ->limit($this->offset, $this->batchLimit);

        $this->data = $this->model->getAll();
    }

    /**
     * 获取最后的SQL
     * @return mixed
     */
    public function getLastSql()
    {
        return $this->model->getLastSql();
    }
}
