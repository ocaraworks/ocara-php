<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Cookie处理类Cookie
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

final class Cookie extends Base
{
	/**
	 * 单例模式
	 */
	private static $_instance;

	private function __clone(){}
	private function __construct(){}

	/**
	 * 获取类对象
	 * @return Session
	 */
	public static function getInstance()
	{
		if (self::$_instance === null) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * 获取cookie变量值
	 * @param string|array $key
	 */
	public static function get($key = false)
	{
		if (func_num_args()) {
			return ocGet($key, $_COOKIE);
		}
		
		return $_COOKIE;
	}

	/**
	 * 设置cookie变量
	 * @param string|array $key
	 * @param mixed $value
	 */
	public static function set($key, $value = false)
	{
		if (ocKeyExists($key, $_COOKIE)) {
			ocSet($_COOKIE, $key, $value);
		}
	}

	/**
	 * 删除cookie变量
	 * @param string|array $key
	 */
	public static function delete($key)
	{
		ocDel($_COOKIE, $key);
	}

	/**
	 * 检测cookie是否设置
	 * @param string|array $key
	 */
	public static function exists($key)
	{
		return ocKeyExists($key, $_COOKIE);
	}

	/**
	 * 新建cookie变量
	 * @param integer $saveTime
	 * @param string $path
	 * @param string $domain
	 * @param bool $secure
	 * @param bool $httponly
	 */
	public static function create($name, $value, $expire = 0, $path = '', $domain = '', $secure = false, $httponly = true)
	{	
		$expire   = intval($expire);
		$expire   = $expire ? time() + $expire : 0;
		$path 	  = $path ? $path : ocConfig('COOKIE.path', OC_EMPTY);
		$domain   = $domain ? $domain : ocConfig('COOKIE.domain', OC_EMPTY);
		$secure   = $secure ? true : ocConfig('COOKIE.secure', false);
		$httponly = $httponly ? true : ocConfig('COOKIE.httponly', true);

		$secure   = $secure ? true : false;
		$httponly = $httponly ? true : false;

		setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
	}
}
