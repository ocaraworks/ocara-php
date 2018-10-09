<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   中间件接口Middleware
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Interfaces;

defined('OC_PATH') or exit('Forbidden!');

interface Middleware
{
	/**
	 * 处理函数
	 * @param array $params
	 * @return mixed
	 */
	public function handler(array $params = array());
}