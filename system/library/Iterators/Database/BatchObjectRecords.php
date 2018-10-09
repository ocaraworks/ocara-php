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
    private $_model;
    private $_sql = array();
    private $_debug;

    private $_position = 0;
    private $_times = 0;
    private $_offset = 0;
    private $_data = array();
    private $_rows = 0;

    /**
     * 初始化
     * BatchObjectRecords constructor.
     * @param string $model
     * @param integer $offset
     * @param integer $rows
     * @param array $sql
     * @param bool $debug
     */
    public function __construct($model, $offset, $rows, array $sql, $debug = false)
    {
        $this->_model = $model;
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
            $class = $this->_model;
            $model = new $class();
            $result = $model->data($data);
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
