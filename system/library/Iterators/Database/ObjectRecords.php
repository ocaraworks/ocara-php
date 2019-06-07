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
    private $model;
    private $debug;
    private $position = 0;
    private $length = 0;
    private $data = array();

    /**
     * 初始化
     * ObjectRecords constructor.
     * @param $model
     * @param array $data
     * @param bool $debug
     */
    public function __construct($model, array $data, $debug = false)
    {
        $this->model = $model;
        $this->debug = $debug;
        $this->data = $data;
        $this->length = count($data);
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
        $data = $this->data[$this->key()];
        $class = $this->model;

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
        $isValid = array_key_exists($this->key(), $this->data);
        return $isValid;
    }

    /**
     * 转换成数组
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }
}
