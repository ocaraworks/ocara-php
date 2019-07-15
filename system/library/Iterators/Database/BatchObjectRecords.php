<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   数据库结果对象迭代器\Ocara\Iterators\Database\BatchObjectRecords
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Iterators\Database;

use \Iterator;

class BatchObjectRecords implements Iterator
{
    protected $model;
    protected $entity;
    protected $sql = array();
    protected $debug;

    protected $position = 0;
    protected $times = 0;
    protected $offset = 0;
    protected $data = array();
    protected $limitRows = 0;

    /**
     * 初始化
     * BatchObjectRecords constructor.
     * @param string $model
     * @param string $entity
     * @param integer $offset
     * @param integer $limitRows
     * @param array $sql
     * @param bool $debug
     */
    public function __construct($model, $entity, $offset, $limitRows, array $sql, $debug = false)
    {
        $this->model = $model;
        $this->entity = $entity;
        $this->offset = $offset;
        $this->sql = $sql;
        $this->debug = $debug;
        $this->limitRows = $limitRows;
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

        if (is_array($data)) {
            $entity = new $this->entity();
            $result = $entity->data($data);
        } else {
            $result = $data;
        }

        return $result;
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
        if ($this->position == $this->limitRows) {
            $this->times++;
            $this->rewind();
        }
    }

    /**
     * 检测合法性
     * @return bool
     */
    function valid()
    {
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

        $list = $model->getAll(null, null, $this->debug);

        $this->data = $list['data'];
        $this->offset += $this->limitRows;
        $this->times++;
    }
}
