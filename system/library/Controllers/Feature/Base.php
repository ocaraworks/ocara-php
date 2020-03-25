<?php
/**
 * 控制器特性基类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Controllers\Feature;

use Ocara\Exceptions\Exception;
use Ocara\Core\Base as ClassBase;

class Base extends ClassBase
{
    /**
     * 获取路由
     * @param $module
     * @param $controller
     * @param array $get
     * @param array $last
     * @return array
     * @throws Exception
     */
    public function getRoute($module, $controller, array $get, $last = array())
    {
        $action = array_shift($get);
        $route = array($module, $controller, $action);

        if (ocService()->url->isVirtualUrl(OC_URL_ROUTE_TYPE)) {
            $_GET = $this->formatGet(array_values($get), $last);
        } else {
            $_GET = $get;
        }

        return $route;
    }

    /**
     * 格式化GET参数
     * @param array $data
     * @param array $last
     * @return array
     */
    public function formatGet(array $data, array $last = array())
    {
        $get = array();

        ksort($data);
        $data = array_chunk($data, 2);

        foreach ($data as $row) {
            if ($row[0]) {
                $get[$row[0]] = isset($row[1]) ? $row[1] : null;
            }
        }

        return $last ? $get + $last : $get;
    }
}