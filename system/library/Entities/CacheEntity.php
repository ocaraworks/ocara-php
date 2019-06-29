<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   缓存模型类Cache
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Models;

use Ocara\Core\BaseEntity;
use Ocara\Core\Models\CacheFactory;
use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

abstract class CacheEntity extends BaseEntity
{
    /**
     * @var string $modelClass
     */
    private $modelClass;

    /**
     * CacheEntity constructor.
     */
    public function __construct()
    {
        $this->setPlugin(new $this->modelClass());
    }

    /**
     * 保存
     * @param array $data
     * @return mixed
     */
    abstract public function save($data = array());

    /**
     * 新建
     * @param array $data
     * @return mixed
     */
    abstract public function create($data = array());

    /**
     * 更新
     * @param array $data
     * @return mixed
     */
    abstract public function update($data = array());

    /**
     * 删除
     * @return mixed
     */
    abstract public function delete();
}
