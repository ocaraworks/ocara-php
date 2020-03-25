<?php
/**
 * 控制器特性类接口
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Interfaces;

interface Feature
{
    /**
     * 获取路由
     * @param string $module
     * @param string $controller
     * @param array $get
     * @return mixed
     */
    public function getRoute($module, $controller, array $get);
}