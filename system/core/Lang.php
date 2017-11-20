<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 语言配置控制类Lang
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

use Ocara\Exception\Exception;

defined('OC_PATH') or exit('Forbidden!');

class Lang extends Base
{
	private static $_data = null;
	private static $_ocData = null;

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
	 * 初始化
	 * @throws Exception
	 */
	public static function init()
	{
		if  (self::$_ocData === null){
			self::$_ocData = array();
			$file = Ocara::language() . '.php';
			$path = OC_SYS . 'data/languages/' . $file;

			if (file_exists($path)) {
				$lang = include ($path);
				if ($lang) {
					self::$_ocData = ocForceArray($lang);
				}
			} else {
				throw new Exception("Lost ocara language file: {$file}.");
			}
		}

		if (self::$_data === null) {
			self::$_data = array();
			self::loadApplicationConfig('lang', Ocara::language(), 'control');
		}
	}
	
	/**
	 * 应用级配置
	 * @param string $dir
	 * @param string $type
	 * $param string $sub
	 */
	public static function loadApplicationConfig($dir, $type, $sub = null)
	{
		$path  = OC_ROOT . 'resource/' . $dir;
		extract(Ocara::getRoute());
		$paths = array();

		if (is_dir($path)) {
			$paths[] = $path;
		}

		if (is_dir($path = $path . OC_DIR_SEP . $type)) {
			$paths[] = $path;
			if ($sub && is_dir($path = $path . OC_DIR_SEP . $sub)) {
				$paths[] = $path;
			}
			if (isset($module) && $module && is_dir($path = $path . OC_DIR_SEP . $module)) {
				$paths[] = $path;
				if ($controller && is_dir($path = $path . OC_DIR_SEP . $controller)) {
					$paths[] = $path;
					if ($action && is_dir($path = $path . OC_DIR_SEP . $action)) {
						$paths[] = $path;
					}
				}
			}
		}

		self::loadControlConfig($paths);
	}

	/**
	 * 加载语言配置
	 * @param string $paths
	 */
	public static function loadControlConfig($paths)
	{
		$LANG = &self::$_data;
		$path = ocForceArray($paths);

		foreach ($path as $value) {
			if ($files = scandir($value)) {
				$config = $LANG;
				foreach ($files as $file) {
					if ($file == '.' or $file == '..') continue;
					$fileType = pathinfo($file, PATHINFO_EXTENSION);
					if (is_file($file = $value . OC_DIR_SEP . $file) && $fileType == 'php') {
						include ($file);
					}
				}
				empty($LANG) && $LANG = $config;
			}
		}
	}
	
	/**
	 * 获取语言信息
	 * @param string $key
	 * @param array $params
	 */
	public static function get($key = null, array $params = array())
	{
		self::init();

		if (func_num_args()) {
			if (ocKeyExists($key, self::$_data)) {
				return ocGetLanguage(self::$_data, $key, $params);
			}
			return self::getDefault($key, $params);
		}
		
		return self::$_data;
	}
	
	/**
	 * 获取默认语言
	 * @param string $key
	 * @param array $params
	 */
	public static function getDefault($key = null, array $params = array())
	{
		self::init();

		if (func_num_args()) {
			return ocGetLanguage(self::$_ocData, $key, $params);
		}
		
		return self::$_ocData;
	}
	
	/**
	 * 设置语言
	 * @param string|array $key
	 * @param mixed $value
	 */
	public static function set($key, $value)
	{
		self::init();
		ocSet(self::$_data, $key, $value);
	}
	
	/**
	 * 检查语言键名是否存在
	 * @param string|array $key
	 */
	public static function exists($key = null)
	{
		self::init();
		return ocKeyExists($key, self::$_data);
	}
	
	/**
	 * 删除语言配置
	 * @param string|array $key
	 */
	public static function del($key)
	{
		self::init();
		return ocDel(self::$_data, $key);
	}
}