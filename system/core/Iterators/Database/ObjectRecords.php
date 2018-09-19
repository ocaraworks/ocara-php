<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   数据库结果对象迭代器\Ocara\Iterators\Database\ObjectRecords
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Iterators\Database;

use \Iterator;

class ObjectRecords implements Iterator
{
    private $_model;
    private $_debug;
    private $_position = 0;
    private $_length = 0;
    private $_data = array();

    /**
     * 初始化
     * ObjectRecords constructor.
     * @param $model
     * @param array $data
     * @param bool $debug
     */
    public function __construct($model, array $data, $debug = false)
    {
        $this->_model = $model;
        $this->_debug = $debug;
        $this->_data = $data;
        $this->_length = count($data);
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
    }

    /**
     * 检测合法性
     * @return bool
     */
    function valid()
    {
        $isValid = array_key_exists($this->key(), $this->_data);
        return $isValid;
    }

    /**
     * 转换成数组
     * @return array
     */
    public function toArray()
    {
        return $this->_data;
    }
}
