<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   pass目录中框架调用函数
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

use Ocara\Ocara;
use Ocara\Url;

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
	 * 初始化
	 * @param string $rootPath
	 * @param string $fileSelf
	 */
	public static function init($rootPath, $fileSelf, $bootstrap = null)
	{
		define('OC_EXECUTE_START_TIME', microtime(true));

		define('OC_ROOT', self::getCommPath(realpath($rootPath)) . '/');
		define('OC_PATH', self::getCommPath(realpath(dirname(dirname(__DIR__)))) . '/');

		define('OC_PHP_SAPI', 'cli');
		define('OC_ROOT_URL', '/');

		define('OC_PHP_SELF',
			ltrim(str_replace(OC_ROOT, '', self::getCommPath(realpath($fileSelf))), '/')
		);

		if (!is_file($path = OC_PATH . '/system/core/Ocara.php')) {
			die('Lost ocara file!');
		}

		include_once($path);
		if (!class_exists('\Ocara\Ocara', false)) {
			die('Lost Ocara class!');
		}

		Ocara::getInstance();
		Ocara::getBootstrap($bootstrap);
	}

    /**
     * 运行
     * @param $route
     * @param array $params
     */
	public static function run($route, $params = array())
    {
        $_GET = $params ? : $_GET;
        $route = Ocara::parseRoute($route);
        Ocara::getBootstrap()->start($route);
    }
}
