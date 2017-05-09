<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   pass目录中框架调用函数
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

final class OcaraInvoke
{
	public static $rootPath;

	/**
	 * 初始化
	 * @param string $rootPath
	 * @param string $fileSelf
	 */
	public static function init($rootPath, $fileSelf)
	{
		define('OC_EXECUTE_START_TIME', microtime(true));
		define('OC_ROOT', self::getCommPath(realpath($rootPath)) . '/');
		define('OC_PATH', self::getCommPath(realpath(dirname(dirname(__DIR__)))) . '/');
		define('OC_PHP_SAPI', 'cli');
		define('OC_URL_ROUTE_TYPE', Url::DIR_TYPE);
		define('OC_ROOT_URL', '/');
		define('OC_PHP_SELF',
			ltrim(str_replace(OC_ROOT, '', self::getCommPath(realpath($fileSelf))), '/')
		);

		if (!is_file($path = OC_PATH . '/system/library/Ocara.php')) {
			die('Lost ocara file!');
		}

		include_once($path);
		if (!class_exists('\Ocara\Ocara', false)) {
			die('Lost Ocara class!');
		}
		Ocara::getInstance();
		Ocara::initialize();
	}

	/**
	 * 运行框架
	 * @param string $route
	 * @param array $params
	 */
	public static function run($route, array $params = array())
	{
		$url = ocUrl($route, $params);
		$_SERVER['argv'][1] = $url;

		if (!ocFileExists(OC_ROOT . '.htaccess')) {
			Ocara::createHtaccess($moreContent = false);
		}
		
		Ocara::boot();
	}

	/**
	 * 目录分隔符替换
	 * @param $path
	 * @return mixed
	 */
	private static function getCommPath($path)
	{
		return str_replace(DIRECTORY_SEPARATOR, '/', $path);
	}

	/**
	 * 获取目录信息
	 */
	private static function _checkPath()
	{
		$filename = self::getCommPath(dirname(realpath($_SERVER['SCRIPT_FILENAME'])));
		$cwdDir   = str_ireplace(OC_ROOT, '', $filename);
		$path     = explode('/', trim($cwdDir, '/'));

		if (in_array(reset($path), array('.', '..'))) {
			array_shift($path);
		}

		if (!($path && reset($path) == 'pass')) {
			die('Access denied!');
		}

		return $cwdDir;
	}
}
