<?php
/**
 
 * Ocara开源框架 数据库结果实体迭代器\Ocara\Iterators\Database\EntityRecords
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Iterators\Database;

use \Iterator;
use Ocara\Core\BaseEntity;

class EntityRecords implements Iterator
{
    protected $length = 0;
    protected $position = 0;
    protected $data = array();
    protected $entity;
    protected $sharding;

    /**
     * EntityRecords constructor.
     * @param $data
     * @param $entity
     */
    public function __construct($data, $entity, $sharding)
    {
        $this->data = $data;
        $this->entity = $entity;
        $this->length = count($this->data);
        $this->sharding = $sharding;
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
        $class = $this->entity;
        $entity = new $class();

        if ($this->sharding) {
            $entity->sharding($this->sharding);
        }

        $entity->dataFrom($this->data[$this->key()]);
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
        $position = $this->key();
        return $this->length && $position < $this->length && !empty($this->data[$position]);
    }
}
