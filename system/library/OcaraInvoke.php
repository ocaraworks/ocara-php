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
	 * 运行框架
	 * @param string $route
	 * @param array $params
	 * @param string $method
	 */
	public static function run($route, array $params = array(), $method = 'GET')
	{
		$url = ocUrl($route, $params);
		$_SERVER['argv'][1] = $url;
		$_SERVER['argv'][2] = $method;
		Ocara::boot();
	}

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
	}
}
