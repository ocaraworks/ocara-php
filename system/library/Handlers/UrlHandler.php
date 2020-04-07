<?php
/**
 * URL事件处理器包
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Handlers;

use Ocara\Core\Base;
use Ocara\Core\Event;

class UrlHandler extends Base
{
    /**
     * 解析URL
     * @param int $urlType
     * @param string $paramsString
     * @param Event $event
     * @param object $eventTarget
     * @return array
     */
    public function parseUrlParams($urlType, $paramsString, $event, $eventTarget)
    {
        return array();
    }

    /**
     * 新建URL
     * @param int $urlType
     * @param string|array $route
     * @param array $params
     * @return null
     */
    public function createUrl($urlType, $route, $params)
    {
        return null;
    }

    /**
     * 追加查询字符串参数
     * @param int $urlType
     * @param array $urlParams
     * @param array $urlInfo
     * @param array $params
     * @return null
     */
    public function appendQueryParams($urlType, $urlParams, $urlInfo, $params)
    {
        return null;
    }
}