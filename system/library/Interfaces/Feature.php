<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 缓存类接口Cache
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Interfaces;

defined('OC_PATH') or exit('Forbidden!');

interface Feature
{
    /**
     * 获取路由
     * @param $module
     * @param $controller
     * @param array $get
     * @return mixed
     */
    public function getRoute($module, $controller, array $get);
}