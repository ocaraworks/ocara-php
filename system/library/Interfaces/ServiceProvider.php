<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 控制器特性类接口Feature
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Interfaces;

defined('OC_PATH') or exit('Forbidden!');

interface ServiceProvider
{
    /**
     * 注册服务组件
     */
    public function register();
}