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
    private $conditions;

    /**
     * 初始化
     * ObjectRecords constructor.
     * @param $model
     * @param array $data
     * @param bool $debug
     */
    public function __construct($model, array $conditions = array(), $debug = false)
    {
        $this->model = $model;
        $this->debug = $debug;
        $this->conditions = $conditions;
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
        $model = new $this->model();
        $result = $model
            ->asEntity()
            ->getRow();

        if (empty($result)) {
            $this->length = $this->key();
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
    }

    /**
     * 检测合法性
     * @return bool
     */
    function valid()
    {
        if (!isset($this->length)) {
            $model = new $this->model();
            foreach ($this->conditions as $condition) {
                $model->where($condition);
            }
            $this->length = $model->getTotal();
        }

        $isValid = $this->key() < $this->length;
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
