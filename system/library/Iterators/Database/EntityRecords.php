<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   数据库结果实体迭代器\Ocara\Iterators\Database\EntityRecords
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Iterators\Database;

use \Iterator;
use Ocara\Core\BaseEntity;

class EntityRecords implements Iterator
{
    protected $length = 0;
    protected $position = 0;
    protected $data = array();
    protected $entity;

    /**
     * EntityRecords constructor.
     * @param $data
     * @param $entity
     */
    public function __construct($data, $entity)
    {
        $this->data = $data;
        $this->entity = $entity;
        $this->length = count($this->data);
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
        $entity = new $this->entity();
        $entity->selectFrom($data);
        return $entity;
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
        return $this->length && $this->key() <= $this->length;
    }
}
