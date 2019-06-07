<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  框架引导类Ocara
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Basis;
use Ocara\Core\Container;
use Ocara\Exceptions\Exception;

//根目录
defined('OC_PATH') OR define(
    'OC_PATH', str_replace("\\", DIRECTORY_SEPARATOR, realpath(dirname(dirname(dirname(__DIR__))))) . DIRECTORY_SEPARATOR
);

defined('OC_EXECUTE_STATR_TIME') OR define('OC_EXECUTE_STATR_TIME', microtime(true));

//加载基本类库
require_once (OC_PATH . 'system/functions/utility.php');
require_once (OC_PATH . 'system/functions/common.php');
require_once (OC_PATH . 'system/const/basic.php');
require_once (OC_CORE . 'Basis.php');

final class Ocara extends Basis
{
	/**
	 * @var $instance 	实例
	 * @var $info 		框架信息
	 */
	private static $instance;
	private static $info;

	private function __clone(){}
	private function __construct(){}

	/**
	 * 单例模式引用
	 */
	public static function getInstance()
	{
		if (self::$instance === null) {
			self::$instance = new self();
			self::register();
		}
		return self::$instance;
	}

	/**
	 * 服务注册
	 */
	public static function register()
	{
	    ocImport(array(
            OC_CORE . 'Base.php',
            OC_CORE . 'Container.php',
            OC_CORE . 'Config.php',
            OC_CORE . 'Loader.php',
        ));

        $container = Container::getDefault()
            ->bindSingleton('config', '\Ocara\Core\Config')
            ->bindSingleton('loader', '\Ocara\Core\Loader')
            ->bindSingleton('path', '\Ocara\Core\Path')
            ->bindSingleton('app', '\Ocara\Core\Application')
            ->bindSingleton('exceptionHandler', '\Ocara\Core\ExceptionHandler');

        $config = $container->config;
        $loader = $container->loader;
        $exceptionHandler = $container->exceptionHandler;

        spl_autoload_register(array($loader, 'autoload'));
        @ini_set('register_globals', 'Off');
        register_shutdown_function("ocShutdownHandler");
        set_exception_handler(array($exceptionHandler, 'exceptionHandler'));
	}

	/**
	 * 新建应用
	 */
	public static function create()
	{
		include_once (OC_CORE . 'Application.php');
        ApplicationGenerator::create();
	}

    /**
     * 运行框架
     * @param string $bootstrap
     * @return mixed
     */
	public static function run($bootstrap = null)
	{
		self::getInstance();

		if (empty($bootstrap)) {
            $bootstrap = defined('OC_BOOTSTRAP') ? OC_BOOTSTRAP : 'Ocara\Bootstraps\Common';
        }

        $application = ocContainer()->app;
        $application->bootstrap($bootstrap);

        $route = $application->parseRoute();
        $result = $application->run($route);

        return $result;
	}

    /**
     * 框架更新
     * @param array $params
     * @return bool
     * @throws Exception
     */
	public static function update(array $params = array())
	{
		ocImport(OC_ROOT . 'pass/Update.php');
		$args = func_get_args();
		return class_exists('Update', false) ? Update::run($args) : false;
	}

    /**
     * 获取框架信息
     * @param null $key
     * @return array|bool|mixed|null
     */
	public static function getInfo($key = null)
	{
		if (is_null(self::$info)) {
			$path = OC_SYS . 'data/framework.php';
			if (ocFileExists($path)) {
				include($path);
			}
			if (isset($FRAMEWORK_INFO) && is_array($FRAMEWORK_INFO)) {
				self::$info = $FRAMEWORK_INFO;
			} else {
				self::$info = array();
			}
		}

		if (isset($key)) {
			return ocGet($key, self::$info);
		}

		return self::$info;
	}
}
