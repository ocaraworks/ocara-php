<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   数据库结果对象迭代器\Ocara\Iterators\Database\EachObjectRecords
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Iterators\Database;

use \Iterator;

class EachObjectRecords implements Iterator
{
    protected $model;
    protected $entity;
    protected $debug;
    protected $result;

    protected $position = 0;
    protected $offset = 0;

    protected $sql = array();

    /**
     * 初始化
     * EachObjectRecords constructor.
     * @param string $model
     * @param string $entity
     * @param integer $offset
     * @param array $sql
     * @param bool $debug
     */
    public function __construct($model, $entity, $offset, array $sql, $debug = false)
    {
        $this->model = $model;
        $this->entity = $entity;
        $this->sql = $sql;
        $this->debug = $debug;
        $this->offset = $offset ? : 0;
    }

    /**
     * 重新开始
     */
    function rewind()
    {
        $this->position = 0;
    }

    /**
     * 获取当前项
     * @return array|mixed
     */
    function current()
    {
        return $this->result;
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
    }

    /**
     * 检测合法性
     * @return bool
     */
    function valid()
    {
        $this->result = $this->getResult();
        return $this->result ? true : false;
    }

    /**
     * 获取记录结果
     * @return mixed
     */
    public function getResult()
    {
        $model = new $this->model();
        $model->setSql($this->sql);

        $result = $model
            ->limit($this->offset, 1)
            ->asEntity($this->entity)
            ->getRow(null, null, $this->debug);

        $this->offset += 1;
        return $result;
    }
}
