<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   控制器类接口Controller
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Interfaces;

defined('OC_PATH') or exit('Forbidden!');

interface Controller
{

	/**
	 * 初始化设置
	 * @param array $route
	 */
	public function init(array $route);

	/**
	 * 执行动作
	 * @param string $actionMethod
	 */
	public function doAction($actionMethod);

	/**
	 * 执行动作（返回值）
	 * @param string $method
	 * @param array $params
	 */
	public function doReturnAction($method, array $params = array());
}