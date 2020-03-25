<?php
/**
 * 数据库单条查询结果对象迭代器
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Iterators\Database;

use \Iterator;
use Ocara\Core\DriverBase;

class EachQueryRecords implements Iterator
{
    protected $model;
    protected $data;

    protected $position = 0;
    protected $offset = 0;

    protected $sql = array();

    /**
     * 初始化
     * EachQueryRecords constructor.
     * @param string $model
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * 重新开始
     */
    function rewind()
    {
        $this->position = 0;
        $this->data = $this->getResult();
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
            ->getRow();

        return $result;
    }

    /**
     * 获取最后的SQL
     * @return array
     */
    public function getLastSql()
    {
        return $this->model->getLastSql();
    }
}
