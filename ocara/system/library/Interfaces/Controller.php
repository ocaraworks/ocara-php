<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   控制器类接口Controller
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Interfaces;
use Ocara\Container;

defined('OC_PATH') or exit('Forbidden!');

interface Controller
{

	/**
	 * 初始化设置
	 * @param array $route
	 */
	public function initialize(array $route);

	/**
	 * 执行动作
	 * @param string $actionMethod
	 * @param bool $display
	 */
	public function doAction($actionMethod, $display = true);

	/**
	 * 执行动作（返回值）
	 * @param string $method
	 * @param array $params
	 */
	public function doReturnAction($method, array $params = array());
}