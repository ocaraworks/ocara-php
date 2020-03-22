<?php
/**
 * 中间件基类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use Ocara\Interfaces\Middleware as MiddlewareInterface;

class Middleware extends ServiceProvider implements MiddlewareInterface
{
    /**
     * 处理
     * @param mixed $args
     * @return mixed
     */
    public function handle($args = null)
    {
    }
}