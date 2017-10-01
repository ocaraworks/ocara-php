<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 配置控制类Config
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

final class Config extends Base
{
	/**
	 * 开关配置
	 */
	const YES = 1;
	const NO = 0;

	/**
	 * 数据变量
	 */
	private static $_data = array();
	private static $_ocaraData = array();
	
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
			self::init();
		}
		return self::$_instance;
	}
	
	/**
	 * 初始化
	 */
	public static function init()
	{
		if (!file_exists($path = OC_SYS . 'data/default.php')) {
			Error::show('Lost ocara config file: default.php.');
		}
		
		include ($path);

		if (!(isset($OC_CONF) && self::$_ocaraData = $OC_CONF)) {
			die('Lost config : $OC_CONF.');
		}

		if (is_dir($path = OC_ROOT . 'resource/conf')) {
			self::loadControlConfig($path);
		}

		self::$_data or die('Lost config : $CONF.');
	}

	/**
	 * 获取基本配置和模块配置
	 * @param string $dir
	 * @param string $type
	 * @param string $sub
	 * @param string $module
	 */
	public static function loadModuleConfig($dir, $type, $sub, $module)
	{
		$path  = OC_ROOT . 'resource/' . $dir;
		$paths = array();
		if (is_dir($path)) {
			$paths[] = $path;
			if (is_dir($path = $path . OC_DIR_SEP . $type)) {
				$paths[] = $path;
				if ($sub && is_dir($path = $path . OC_DIR_SEP . $sub)) {
					$paths[] = $path;
				}
				if ($module && is_dir($path = $path . OC_DIR_SEP . $module)) {
					$paths[] = $path;
				}
			}
		}

		self::loadControlConfig($paths);
	}

	/**
	 * 加载控制器动作的配置
	 * @param string $path
	 */
	public static function loadActionConfig($path)
	{
		$path = OC_ROOT . 'resource/' . rtrim($path, OC_DIR_SEP);
		$paths = array();
		extract(Ocara::getRoute());

		if (isset($module) && $module && is_dir($path . OC_DIR_SEP . $module)) {
			$path = $path . OC_DIR_SEP . $module;
			$paths[] = $path;
		}

		if ($controller && is_dir($path = $path . OC_DIR_SEP . $controller)) {
			$paths[] = $path;
			if ($action && is_dir($path = $path . OC_DIR_SEP . $action)) {
				$paths[] = $path;
			}
		}

		self::loadControlConfig($paths);
	}

	/**
	 * 应用级配置
	 * @param string $dir
	 * @param string $type
	 * $param string $sub
	 * @param string $module
	 */
	public static function loadApplicationConfig($dir, $type, $sub = null, $module = null)
	{
		self::loadModuleConfig($dir, $type, $sub, $module);
		self::loadActionConfig(ocDir(array($dir, $type, $sub)));
	}

	/**
	 * 加载配置
	 * @param string $path
	 */
	public static function loadControlConfig($path)
	{
		$CONF = &self::$_data;
		$path = ocForceArray($path);

		foreach ($path as $value) {
			if ($files = scandir($value)) {
				$config = $CONF;
				foreach ($files as $file) {
					if ($file == '.' or $file == '..') continue;
					$fileType = pathinfo($file, PATHINFO_EXTENSION);
					$file = $value . OC_DIR_SEP . $file;
					if (is_file($file) && $fileType == 'php') {
						include ($file);
					}
				}
				empty($CONF) && $CONF = $config;
			}
		}
	}
	
	/**
	 * 设置配置
	 * @param string $key
	 * @param mixed $value
	 */
	public static function set($key, $value)
	{
		ocSet(self::$_data, $key, $value);
	}
	
	/**
	 * 获取配置
	 * @param string $key
	 */
	public static function get($key = null)
	{
		if (func_num_args()) {
			if (ocKeyExists($key, self::$_data)) {
				return ocGet($key, self::$_data);
			}
			return self::getDefault($key);
		}
		
		return self::$_data;
	}
	
	/**
	 * 获取默认配置
	 * @param string $key
	 */
	public static function getDefault($key = null)
	{
		if (func_num_args()) {
			return ocGet($key, self::$_ocaraData);
		}

		return self::$_ocaraData;
	}
	
	/**
	 * 检查配置键名是否存在
	 * @param string $key
	 */
	public static function exists($key = null)
	{
		return ocKeyExists($key, self::$_data);
	}
	
	/**
	 * 获取配置
	 * @param string $key
	 */
	public static function getConfig($key)
	{
		if ($result = ocCheckKey(false, $key, self::$_data, true) OR
			($result = ocCheckKey(false, $key, self::$_ocaraData, true))
		) {
			return $result;
		}
		
		return array();
	}
	
	/**
	 * 删除配置
	 * @param string $key
	 */
	public static function del($key)
	{
		return ocDel(self::$_data, $key);
	}
}