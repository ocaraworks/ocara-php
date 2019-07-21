<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   数据库结果对象迭代器\Ocara\Iterators\Database\EachSqlRecords
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Iterators\Database;

use \Iterator;
use Ocara\Core\DriverBase;

class EachSqlRecords implements Iterator
{
    protected $model;
    protected $dataType;
    protected $debug;
    protected $data;

    protected $position = 0;
    protected $offset = 0;

    protected $sql = array();

    /**
     * 初始化
     * EachSqlRecords constructor.
     * @param string $model
     * @param string $dataType
     * @param array $sql
     * @param bool $debug
     */
    public function __construct($model, $dataType, array $sql, $debug = false)
    {
        $this->model = $model;
        $this->sql = $sql;
        $this->debug = $debug;
        $this->dataType = $dataType ?: DriverBase::DATA_TYPE_ARRAY;
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
        return $this->data;
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
        $this->position++;
        $this->data = $this->getResult();
    }

    /**
     * 检测合法性
     * @return bool
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
        $this->offset = $this->position * 1;

        $model = new $this->model();
        $model->setSql($this->sql);

        $result = $model
            ->limit($this->offset, 1)
            ->setDataType($this->dataType)
            ->getRow(null, null, $this->debug);

        return $result;
    }
}
