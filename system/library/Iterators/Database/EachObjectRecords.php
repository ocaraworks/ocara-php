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
    protected $_model;
    protected $_entity;
    protected $_debug;
    protected $_result;

    protected $_position = 0;
    protected $_offset = 0;

    protected $_sql = array();

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
        $this->_model = $model;
        $this->_entity = $entity;
        $this->_sql = $sql;
        $this->_debug = $debug;
        $this->_offset = $offset ? : 0;
    }

    /**
     * 重新开始
     */
    function rewind()
    {
        $this->_position = 0;
    }

    /**
     * 获取当前项
     * @return array|mixed
     */
    function current()
    {
        return $this->_result;
    }

    /**
     * 获取当前位置
     * @return int
     */
    function key()
    {
        return $this->_position;
    }

    /**
     * 跳到下一个位置
     */
    function next()
    {
        $this->_position++;
    }

    /**
     * 检测合法性
     * @return bool
     */
    function valid()
    {
        $this->_result = $this->getResult();
        return $this->_result ? true : false;
    }

    /**
     * 获取记录结果
     * @return mixed
     */
    public function getResult()
    {
        $model = new $this->_model();
        $model->setSql($this->_sql);

        $result = $model
            ->limit($this->_offset, 1)
            ->asEntity($this->_entity)
            ->findRow(null, null, $this->_debug);

        $this->_offset += 1;
        return $result;
    }
}
