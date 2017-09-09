<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 全局变量类GlobalVar
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

class GlobalVar extends Base
{
	private static $_data = array();
	
	/**
	 * 单例模式
	 */
	private static $_instance = null;
	
	private function __clone(){}
	private function __construct(){}

	public static function getInstance()
	{
		if (self::$_instance === null) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	/**
	 * 设置全局变量
	 * @param string $name
	 * @param mixed $value
	 */
	public static function set($name, $value)
	{
		self::$_data[$name] = $value;
	}
	
	/**
	 * 获取全局变量
	 * @param string $name
	 */
	public static function get($name = null)
	{
		if (func_num_args()) {
			if(array_key_exists($name, self::$_data)) {
				return self::$_data[$name];
			}
			return null;
		}
		
		return self::$_data;
	}
	
	/**
	 * 检查键名是否存在
	 * @param string $name
	 */
	public static function exists($name = null)
	{
		return array_key_exists($name, self::$_data);
	}
	
	/**
	 * 删除全局变量
	 * @param string $name
	 */
	public static function del($name)
	{
		if (array_key_exists($name, self::$_data)) {
			self::$_data[$name] = null;
			unset(self::$_data[$name]);
		}
	}
}