<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   缓存模型类Cache
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Entities;

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
}
