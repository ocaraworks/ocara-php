<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   服务组件基类ServiceBase
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Base;
use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class ServiceBase extends Base
{
	private static $lang = null;
	private $error;

	public function __construct()
    {
        if (self::$lang === null) {
            $reflection = new \ReflectionObject($this);
            self::loadLanguage($reflection->getFileName());
        }
    }

    /**
	 * 加载语言文件
	 * @param string $filePath
	 */
	public static function loadLanguage($filePath)
	{
        $parentPath = ocCommPath(dirname($filePath));
	    $subPath = str_replace($parentPath, '', ocCommPath($filePath));

        $path = $parentPath . '/Languages/'
			. ucfirst(ocService()->app->getLanguage())
			. $subPath;

		if (ocFileExists($path)) {
			$config = include($path);
			if ($config && is_array($config)) {
                self::$lang[self::getClass()] = $config;
			}
		}
	}

	/**
	 * 获取语言配置信息
	 * @param string $key
	 * @param array $params
	 * @return array
	 */
	public static function getLanguage($key, array $params = array())
	{				
		$class = self::getClass();

		if ($class && array_key_exists($class, self::$lang)) {
			$languages = self::$lang[$class];
		} else {
			$languages = array();
		}

		return ocGetLanguage($languages, $key, $params);
	}

	/**
	 * 类文件是否存在
	 * @param string $classFile
	 * @return array
	 */
	public static function classFileExists($classFile)
	{
		if ($classFile) {
			if (ocFileExists($path = OC_LIB . $classFile)) {
				return array($path, 'Ocara' . OC_NS_SEP);
			}
			if (ocFileExists($path = OC_EXT . $classFile)) {
				return array($path, 'Ocara\Core\Extension' . OC_NS_SEP);
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
     * @return mixed
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
	 * @throws Exception
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
		return empty($this->error) ? false : true;
	}
	
	/**
	 * 获取错误信息
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * 设置错误信息
	 * @param string $name
	 * @param array $params
	 * @return bool
	 */
	protected function setError($name, array $params = array())
	{
		$this->error = self::getLanguage($name, $params);
		return false;
	}
}