<?php
/**
 * Route事件处理器包
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Handlers;

use Ocara\Core\Base;
use Ocara\Core\Event;

class RouteHandler extends Base
{
    /**
     * 获取路由后置处理
     * @param array $route
     * @param array $get
     * @param Event $event
     * @param object $eventSource
     * @return mixed
     */
    public function afterGetRoute($route, $get, $event, $eventSource)
    {
        return $get;
    }
}