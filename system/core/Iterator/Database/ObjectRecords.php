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
    private $_model;
    private $_condition;
    private $_options;
    private $_debug;

    private $_position = 0;
    private $_times = 0;
    private $_isBatch = false;
    private $_offset = 0;
    private $_data = array();
    private $_rows = 0;

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
    }

    /**
     * 重新开始
     */
    function rewind()
    {
        $this->getResult();

        if (!($this->_rows || $this->_isBatch)) {
            $this->_rows = count($this->_data);
        }

        $this->_position = 0;
    }

    /**
     * 批量查询
     * @param int $rows
     * @param int $start
     * @return $this
     */
    public function batch($rows, $start = 0)
    {
        $this->_isBatch = true;
        $this->_offset = $start;
        $this->_rows = $rows;
        return $this;
    }

    /**
     * 获取当前项
     * @return array|mixed
     */
    function current()
    {
        $data = $this->_data[$this->key()];
        $class = $this->_model;

        $model = new $class();
        $result = $model->data($data);

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
            if ($this->_isBatch) {
                $this->_times++;
                $this->rewind();
            }
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

        if ($this->_rows) {
            $model->limit($this->_offset, $this->_rows);
        }

        foreach ($this->_condition as $condition) {
            $model->where($condition);
        }

        $this->_data = $model->getAll(false, $this->_options);
        $this->_offset += $this->_rows;
        $this->_times++;
    }
}
