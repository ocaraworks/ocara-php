<?php
/**
 * 缓存实体基类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Entities;

use Ocara\Core\BaseEntity;
use Ocara\Core\Models\CacheFactory;
use Ocara\Exceptions\Exception;

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
}
