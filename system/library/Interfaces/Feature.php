<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   缓存类接口Cache
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
	 * @param array $get
	 * @return array|bool|mixed|null
	 */
	public function getAction(array $get);

	/**
	 * 设置最终路由
	 * @param string $module
	 * @param string $controller
	 * @param string $action
	 * @return array
	 */
	public function getLastRoute($module, $controller, $action);
}