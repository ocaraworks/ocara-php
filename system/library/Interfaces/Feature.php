<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   控制器特性类接口Feature
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Interfaces;

defined('OC_PATH') or exit('Forbidden!');

interface Feature
{
	/**
	 * 获取路由
	 * @param string $action
	 * @param bool $isModule
	 * @param bool $isStandard
	 * @return bool|null|string
	 */
	public static function getControllerAction($action, $isModule = false, $isStandard = false);

	/**
	 * 设置最终路由
	 * @param $module
	 * @param $controller
	 * @param $action
	 */
	public static function getDefaultRoute($module, $controller, $action);
}