<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   数据库结果迭代器\Ocara\Iterator\DatabaseResult
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Iterator\Database;

class ArrayRecords implements \ArrayIterator
{
    private $position;
    private $count;
    private $model;
    private $limit;

    public function __construct($model, $max = 1, $offset = 0, $limit = 1)
    {
        $this->model = $model;
        $this->position = $start;
        $this->limit = $start + $limit;
        $this->count = 0;
    }

    function rewind()
    {
        $this->position = 0;
    }

    function current()
    {
        return $this->model->limit($this->position, 1)->findFirst();
    }

    function key()
    {
        return $this->position;
    }

    function next()
    {
        ++$this->position;
        ++$this->count;
    }

    function valid()
    {
        return $this->count <= $this->limit;
    }
}
