<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   数据库结果对象迭代器\Ocara\Iterators\Database\EachQueryRecords
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Iterators\Database;

use \Iterator;
use Ocara\Core\DriverBase;

class EachQueryRecords implements Iterator
{
    protected $model;
    protected $debug;
    protected $data;

    protected $position = 0;
    protected $offset = 0;

    protected $sql = array();

    /**
     * 初始化
     * EachQueryRecords constructor.
     * @param string $model
     * @param bool $debug
     */
    public function __construct($model, $debug = false)
    {
        $this->model = $model;
        $this->debug = $debug;
    }

    /**
     * 重新开始
     */
    function rewind()
    {
        $this->position = 0;
        $this->getResult();
    }

    /**
     * 获取当前项
     * @return array|mixed
     */
    function current()
    {
        return $this->data;
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
        $this->data = $this->getResult();
    }

    /**
     * 检测合法性
     * @return bool
     */
    function valid()
    {
        return $this->data ? true : false;
    }

    /**
     * 获取记录结果
     * @return mixed
     */
    public function getResult()
    {
        $this->offset = $this->position * 1;

        $result = $this->model
            ->limit($this->offset, 1)
            ->getRow(null, null, $this->debug);

        return $result;
    }
}
