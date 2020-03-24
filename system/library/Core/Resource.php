<?php
/**
 * 资源中间件类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use Ocara\Interfaces\Resource as ResourceInterface;

class Resource extends ServiceProvider implements ResourceInterface
{
    /**
     * 获取资源
     * @param mixed $args
     * @return mixed
     */
    public function handle($args = null)
    {
    }
}