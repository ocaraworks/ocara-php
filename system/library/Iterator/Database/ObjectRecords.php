<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   数据库结果迭代器\Ocara\Iterator\Database\ObjectRecords
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Iterator\Database;

class ObjectRecords implements \Iterator
{
    private $_position;
    private $_times;
    private $_offset;
    private $_rows;
    private $_result;

    private $_model;
    private $_condition;
    private $_options;
    private $_debug;

    public function __construct($model, $condition, $options = null, $debug = false)
    {
        $this->_model = $model;
        $this->_condition = $condition;
        $this->_options = $options;
        $this->_debug = $debug;
        $this->_position = 0;
    }

    public function setLimit($times, $start = 0, $rows = 1)
    {
        $this->_times = $times;
        $this->_offset = $start;
        $this->_rows = $rows;
    }

    function rewind()
    {
        $this->_position = 0;
    }

    function current()
    {
        if ($this->_times) {
            $result = $this->_getCurrentResult($this->_offset);
        } else {
            $result = $this->_result;
        }

        return $result;
    }

    function key()
    {
        return $this->_position;
    }

    function next()
    {
        $this->_position++;
        if ($this->_times) {
            $this->_offset += $this->_rows;
        }
    }

    function valid()
    {
        if ($this->_times) {
            return $this->_position <= $this->_times;
        }

        $this->_result = $this->_getCurrentResult();
        $isValid = $this->_result ? true : false;
        $this->_offset += $this->_rows;

        return $isValid;
    }

    protected function _getCurrentResult()
    {
        if ($this->_rows > 1) {
            $result = array();
            for($start = $this->_offset; $start < $this->_rows; $start++) {
                $result[] = $this->_getRow($start);
            }
        } else {
           $result = $this->_getRow($this->_offset);
        }

        return $result;
    }

    protected function _getRow($start)
    {
        $model = new $this->_model();
        $model->limit($start, 1);

        foreach ($this->_condition as $condition) {
            $model->where($condition);
        }

        $result = $model->selectOne(false, $this->_options);

        return $result;
    }
}
