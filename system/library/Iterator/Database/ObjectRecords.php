<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   数据库结果对象迭代器\Ocara\Iterator\Database\ObjectRecords
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

    /**
     * 初始化
     * ObjectRecords constructor.
     * @param $model
     * @param $condition
     * @param null $options
     * @param bool $debug
     */
    public function __construct($model, $condition, $options = null, $debug = false)
    {
        $this->_model = $model;
        $this->_condition = $condition;
        $this->_options = $options;
        $this->_debug = $debug;
        $this->_position = 0;
    }

    /**
     * 设置分页
     * @param $times
     * @param int $start
     * @param int $rows
     */
    public function setLimit($times, $start = 0, $rows = 1)
    {
        $this->_times = $times;
        $this->_offset = $start;
        $this->_rows = $rows;
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
        if ($this->_times) {
            $result = $this->_getCurrentResult($this->_offset);
        } else {
            $result = $this->_result;
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
        if ($this->_times) {
            $this->_offset += $this->_rows;
        }
    }

    /**
     * 检测合法性
     * @return bool
     */
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

    /**
     * 查询当前结果
     * @return array|mixed
     */
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

    /**
     * 查询当前一行
     * @param $start
     * @return mixed
     */
    protected function _getRow($start)
    {
        $model = new $this->_model();
        $model->limit($start, 1);

        foreach ($this->_condition as $condition) {
            $model->where($condition);
        }

        $result = $model->findRow(false, $this->_options);

        return $result;
    }
}
