<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   模板插件接口Template
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Service\Interfaces;

defined('OC_PATH') or exit('Forbidden!');

/**
 * 模板插件接口
 * @author Administrator
 *
 */
interface Template
{
    /**
     * 设置变量
     * @param string $name
     * @param mixed $value
     */
    function set($name, $value);

    /**
     * 注册对象
     * @param array $params
     */
    function registerObject($params);

    /**
     * 注册插件
     * @param string $params
     */
    function registerPlugin($params);

    /**
     * 获取变量
     * @param string $name
     */
    function get($name = null);
}