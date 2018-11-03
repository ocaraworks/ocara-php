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
	 */
	public function init();

	/**
	 * 执行动作
	 * @param string $actionMethod
	 */
	public function doAction($actionMethod);
}