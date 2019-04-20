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
    protected $_model;
    protected $_entity;
    protected $_sql = array();
    protected $_debug;

    protected $_position = 0;
    protected $_times = 0;
    protected $_offset = 0;
    protected $_data = array();
    protected $_rows = 0;

    /**
     * 初始化
     * BatchObjectRecords constructor.
     * @param string $model
     * @param string $entity
     * @param integer $offset
     * @param integer $rows
     * @param array $sql
     * @param bool $debug
     */
    public function __construct($model, $entity, $offset, $rows, array $sql, $debug = false)
    {
        $this->_model = $model;
        $this->_entity = $entity;
        $this->_offset = $offset;
        $this->_sql = $sql;
        $this->_debug = $debug;
        $this->_rows = $rows;
    }

    /**
     * 重新开始
     */
    function rewind()
    {
        $this->getResult();
        $this->_position = 0;
    }

    /**
     * 获取当前项
     * @return array|mixed
     */
    function current()
    {
        $data = $this->_data[$this->key()];

        if (is_array($data)) {
            $entity = new $this->_entity();
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
        return $this->_position;
    }

    /**
     * 跳到下一个位置
     */
    function next()
    {
        $this->_position++;
        if ($this->_position == $this->_rows) {
            $this->_times++;
            $this->rewind();
        }
    }

    /**
     * 检测合法性
     * @return bool
     */
    function valid()
    {
        $isValid = $this->_position < $this->_rows && array_key_exists($this->key(), $this->_data);
        return $isValid;
    }

    /**
     * 获取记录结果
     * @return mixed
     */
    public function getResult()
    {
        $model = new $this->_model();
        $model->setSql($this->_sql);
        $model->limit($this->_offset, $this->_rows);

        $list = $model->getAll(null, null, $this->_debug);

        $this->_data = $list['data'];
        $this->_offset += $this->_rows;
        $this->_times++;
    }
}
