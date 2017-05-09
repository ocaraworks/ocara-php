<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Session处理类Session
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

final class Session extends Base
{
	const SAVE_TYPE_FILE     = 1;
	const SAVE_TYPE_DATABASE = 2;
	const SAVE_TYPE_CACHE    = 3;

	/**
	 * 单例模式
	 */
	private static $_instance;

	private function __clone(){}
	private function __construct(){}

	/**
	 * 获取类对象
	 * @param bool $start
	 * @return Session
	 */
	public static function getInstance($start = true)
	{
		if (self::$_instance === null) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Session初始化处理
	 * @param $start
	 */
	public static function initialize($start = true)
	{
		$saveType = ocConfig('SESSION.save_type', Session::SAVE_TYPE_FILE);

		if ($saveType == Session::SAVE_TYPE_FILE) {
			$class = 'Ocara\Session\SessionFile';
		} elseif ($saveType == Session::SAVE_TYPE_DATABASE) {
			$class  = 'Ocara\Session\SessionDB';
		} elseif ($saveType == Session::SAVE_TYPE_CACHE) {
			$class  = 'Ocara\Session\SessionCache';
		} else {
			$class = ocConfig('SESSION.handler', false);
		}

		if ($class) {
			$handler = new $class();
			session_set_save_handler(
				array(&$handler, 'open'),
				array(&$handler, 'close'),
				array(&$handler, 'read'),
				array(&$handler, 'write'),
				array(&$handler, 'destroy'),
				array(&$handler, 'gc')
			);
			register_shutdown_function('session_write_close');
		}

		self::boot($start);
	}

	/**
	 * 启动Session
	 * @param bool $start
	 */
	private static function boot($start)
	{
		$saveTime = intval(ocConfig('SESSION.save_time', false));

		if ($saveTime) {
			self::setSaveTime($saveTime);
		}

		if ($start && !isset($_SESSION)) {
			if (!headers_sent()) {
				session_start();
			}
		}
		
		if ($saveTime) {
			self::setCookie($saveTime);
		}
	}

	/**
	 * 获取session变量值
	 * @param string|array $key
	 */
	public static function get($key = false)
	{
		if (func_num_args()) {
			return ocGet($key, $_SESSION);
		}
		
		return $_SESSION;
	}

	/**
	 * 设置session变量
	 * @param string|array $key
	 * @param mixed $value
	 */
	public static function set($key, $value = false)
	{
		ocSet($_SESSION, $key, $value);
	}

	/**
	 * 删除session变量
	 * @param string|array $key
	 */
	public static function delete($key)
	{
		ocDel($_SESSION, $key);
	}

	/**
	 * 获取session ID
	 */
	public static function getId()
	{
		return session_id();
	}

	/**
	 * 获取session Name
	 */
	public static function getName()
	{
		return session_name();
	}

	/**
	 * 清空session数组
	 */
	public static function clear()
	{
		session_unset();
	}
	
	/**
	 * 检测session是否设置
	 * @param string|array $key
	 */
	public static function exists($key)
	{
		return ocKeyExists($key, $_SESSION);
	}

	/**
	 * 释放session，删除session文件
	 */
	public static function destroy()
	{
		if (session_id()) {
			return session_destroy();
		}
	}

	/**
	 * cookie保存session设置
	 * @param integer $saveTime
	 * @param string $path
	 * @param string $domain
	 * @param bool $secure
	 * @param bool $httponly
	 */
	public static function setCookie($saveTime, $path = false, $domain = false, $secure = false, $httponly = true)
	{	
		if (session_id()) {
			Cookie::create(session_name(), session_id(), $saveTime, $path, $domain, $secure, $httponly);
		}
	}

	/**
	 * 设置session有效期(单位为秒)
	 * @param integer $saveTime
	 */
	public static function setSaveTime($saveTime)
	{
		return @ini_set('session.gc_maxlifetime', $saveTime);
	}

	/**
	 * 序列化session数组
	 */
	public static function serialize()
	{
		return session_encode();
	}

	/**
	 * 反序列化session串
	 * @param string $data
	 */
	public static function unserialize($data)
	{
		return session_decode($data);
	}
}
