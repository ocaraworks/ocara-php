<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   服务组件基类ServiceBase
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

class ServiceBase extends Base
{
	private static $_lang = array();
	private $_error;
	
	/**
	 * 加载语言文件
	 * @param string $filePath
	 * @param string $className
	 */
	public static function loadLanguage($filePath, $className)
	{
		$filePath = '/languages/' . Ocara::language() . OC_DIR_SEP . $filePath;
		$lang     = array();

		if (ocFileExists($path = OC_SYS . 'service' . $filePath)) {
			$config = include($path);
			if ($config && is_array($config)) {
				$lang = $config;
			}
		}
		
		if (ocFileExists($path = OC_EXT . 'service' . $filePath)) {
			$config = include($path);
			if ($config && is_array($config)) {
				$lang = array_merge($lang, $config);
			}
		}
		
		if ($lang) {
			self::$_lang[$className] = $lang;
		}
	}
	
	/**
	 * 获取语言配置信息
	 * @param string $key
	 * @param array $params
	 */
	public static function getLanguage($key, array $params = array())
	{				
		$class = get_called_class();

		if ($class && array_key_exists($class, self::$_lang)) {
			$languages = self::$_lang[$class];
		} else {
			$languages = array();
		}
		
		return ocGetLanguage($languages, $key, $params);
	}

	/**
	 * 类文件是否存在
	 * @param string $classFile
	 */
	public static function classFileExists($classFile)
	{
		if ($classFile) {
			if (ocFileExists($path = OC_LIB . $classFile)) {
				return array($path, 'Ocara' . OC_NS_SEP);
			}
			if (ocFileExists($path = OC_EXT . $classFile)) {
				return array($path, 'Ocara\Extension' . OC_NS_SEP);
			}
			if (ocFileExists($path = ocPath('library', $classFile))) {
				return array($path, OC_NS_SEP);
			}
		}

		return array();
	}

	/**
	 * 获取语言内容
	 * @param string $key
	 * @param array $params
	 */
	public static function getMessage($key, array $params = array())
	{
		$language = self::getLanguage($key, $params);
		return $language['message'];
	}

	/**
	 * 显示错误信息
	 * @param string $error
	 * @param array $params
	 */
	public static function showError($error, array $params = array())
	{
		$error = self::getLanguage($error, $params);

		throw new Exception(
			str_ireplace('%s', OC_EMPTY, $error['message']), $error['code']
		);
	}
	
	/**
	 * 错误是否存在
	 */
	public function errorExists()
	{
		return empty($this->_error) ? false : true;
	}
	
	/**
	 * 获取错误信息
	 */
	public function getError()
	{
		return $this->_error;
	}

	/**
	 * 设置错误信息
	 * @param string $name
	 * @param array $params
	 */
	protected function setError($name, array $params = array())
	{
		$this->_error = self::getLanguage($name, $params);
		return false;
	}
}