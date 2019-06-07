<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   pass目录中框架调用函数
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use \Exception;

final class Invoke
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
     * @param string $bootstrap
     * @throws Exception
     */
	public function init($bootstrap = null)
	{
        defined('OC_ROOT') OR die('forbidden');

        defined('OC_EXECUTE_START_TIME') OR define('OC_EXECUTE_START_TIME', microtime(true));
        defined('OC_PATH') OR define('OC_PATH', self::getCommPath(realpath(dirname(dirname(dirname(__DIR__))))) . '/');
        defined('OC_INVOKE') OR define('OC_INVOKE', true);

		if (!is_file($path = OC_PATH . 'system/library/Core/Ocara.php')) {
			throw new Exception('Lost ocara file!');
		}

		include_once($path);
		if (!class_exists('\Ocara\Core\Ocara', false)) {
            throw new Exception('Lost Ocara class!');
		}

		Ocara::getInstance();
        ocContainer()->app->bootstrap($bootstrap);
	}

    /**
     * 运行路由
     * @param $route
     * @param array $params
     * @param string $requestMethod
     */
	public static function run($route, $params = array(), $requestMethod = 'GET')
    {
        if ($params) {
            if ($requestMethod == 'GET'){
                $_GET = array_merge($_GET, $params);
            }
        }

        $app = ocService()->app;
        $route = $app->formatRoute($route);
        $app->setRoute($route);
        $app->bootstrap()->start($app->getRoute());
    }
}
