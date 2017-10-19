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

interface Bootstrap
{
	/**
	 * 初始化
	 */
	public function init();

	/**
	 * 运行访问控制器
	 * @param string|array $route
	 * @return mixed
	 */
	public function run($route);

	/**
	 * 获取默认服务容器
	 * @return string
	 */
	public function getContainer();

	/**
	 * 获取默认服务提供器
	 * @return string
	 */
	public function getServiceProvider();
}