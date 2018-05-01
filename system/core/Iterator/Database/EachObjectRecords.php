<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   数据库结果对象迭代器\Ocara\Iterator\Database\ObjectRecords
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Iterator\Database;

class EachObjectRecords implements \Iterator
{
    private $_model;
    private $_debug;
    private $_result;

    private $_position = 0;
    private $_offset = 0;

    private $_sql = array();

    /**
     * 初始化
     * EachObjectRecords constructor.
     * @param string $model
     * @param integer $offset
     * @param array $sql
     * @param bool $debug
     */
    public function __construct($model, $offset, array $sql, $debug = false)
    {
        $this->_model = $model;
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
            ->findRow(null, null, $this->_debug);

        $this->_offset += 1;
        return $result;
    }
}
