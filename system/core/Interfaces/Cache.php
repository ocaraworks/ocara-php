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

interface Cache
{
	/**
	 * 析构函数
	 * @param array $config
	 * @param bool $required
	 */
	public function connect($config, $required = true);

	/**
	 * 设置KEY
	 * @param string $name
	 * @param boolean $value
	 * @param integer $expireTime
	 * @param mixed $args
	 * @return bool
	 */
	public function set($name, $value, $expireTime = 0, $args = null);
	
	/**
	 * 获取KEY
	 * @param string $name
	 */
	public function get($name);

	/**
	 * 删除KEY
	 * @param string $name
	 */
	public function delete($name);

	/**
	 * 选择数据库
	 * @param string $name
	 */
	public function selectDatabase($name);
}