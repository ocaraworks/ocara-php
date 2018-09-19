<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  框架引导类Ocara
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

use Ocara\Basis;
use Ocara\Container;
use Ocara\Config;
use Ocara\Loader;
use Ocara\ExceptionHandler;

defined('OC_EXECUTE_STATR_TIME') OR define('OC_EXECUTE_STATR_TIME', microtime(true));

//根目录
defined('OC_PATH') OR define(
    'OC_PATH', str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname(dirname(__DIR__)))) . '/'
);

require_once (OC_PATH . 'system/functions/utility.php');
require_once (OC_PATH . 'system/const/basic.php');
require_once (OC_CORE . 'Basis.php');
require_once (OC_CORE . 'Container.php');
require_once (OC_CORE . 'Config.php');
require_once (OC_CORE . 'Loader.php');
require_once (OC_CORE . 'ExceptionHandler.php');

final class Ocara extends Basis
{
	/**
	 * @var $OC_CONF 	框架信息
	 * @var $CONF 		公共配置 
	 * @var $OC_LANG    框架语言数据
	 */
    private static $_bootstrap;
	private static $_services;
	private static $_instance;
	private static $_info;
	private static $_language;

	private static $_route = array();

	private function __clone(){}
	private function __construct(){}

	/**
	 * 单例模式引用
	 */
	public static function getInstance()
	{
		if (self::$_instance === null) {
			self::$_instance = new self();
			self::init();
		}
		return self::$_instance;
	}

    /**
     * 获取全局默认容器
     * @return mixed
     */
	public static function container()
    {
        return Container::getDefault();
    }

	/**
	 * 初始化设置
	 */
	public static function init()
	{
        $container = self::container()
            ->bindSingleton('config', '\Ocara\Config')
            ->bindSingleton('loader', '\Ocara\Loader')
            ->bindSingleton('path', '\Ocara\Path')
            ->bindSingleton('exceptionHandler', '\Ocara\ExceptionHandler');

        $config = $container->config;
        $loader = $container->loader;
        $exceptionHandler = $container->exceptionHandler;

        spl_autoload_register(array($loader, 'autoload'));
        $config->loadGlobalConfig();

        define('OC_SYS_MODEL', $config->get('SYS_MODEL', 'application'));
        define('OC_LANGUAGE', $config->language());

        @ini_set('register_globals', 'Off');
        register_shutdown_function("ocShutdownHandler");
        error_reporting(self::errorReporting());
        set_exception_handler(array($exceptionHandler, 'run'));

        if (empty($_SERVER['REQUEST_METHOD'])) {
            $_SERVER['REQUEST_METHOD'] = 'GET';
        }

        ocImport(array(
            OC_SYS . 'const/config.php',
            OC_SYS . 'functions/common.php'
        ));
	}

	/**
	 * 新建应用
	 */
	public static function create()
	{
		include_once (OC_CORE . 'Application.php');
		Application::create();
	}

	/**
	 * 运行框架
	 * @param string $bootstrap
	 */
	public static function run($bootstrap = null)
	{
		self::getInstance();
		self::bootstrap($bootstrap);

		self::getRoute();
		self::$_bootstrap->start(self::$_route);
	}

	/**
	 * 获取或设置启动器
	 * @param $bootstrap
	 * @return string
	 */
	public static function bootstrap($bootstrap = null)
	{
	    if (func_num_args()) {
            $bootstrap = $bootstrap ? : '\Ocara\Bootstrap';
            self::$_bootstrap = new $bootstrap();
            self::$_services = self::$_bootstrap->getServiceProvider();
            self::$_services->setContainer(Container::getDefault());
            self::$_services->register();
            self::$_bootstrap->init();
        }

		return self::$_bootstrap;
	}

	/**
	 * 规定在哪个错误报告级别会显示用户定义的错误
	 * @param integer $error
	 * @return bool|int
	 */
	public static function errorReporting($error = null)
	{
		$error = $error ? : (OC_SYS_MODEL == 'develop' ? E_ALL : 0);

		set_error_handler(
            Container::getDefault()->config->get('ERROR_HANDLER.program_error', 'ocErrorHandler'),
			$error
		);

		return $error;
	}

	/**
	 * 获取路由信息
	 * @param string $name
	 * @return array|null
	 */
	public static function getRoute($name = null)
	{
		if (!self::$_route) {
		    if (!OC_INVOKE) {
                $_GET = self::$_services->url->parseGet();
            }
			list($module, $controller, $action) = self::$_services->route->parseRouteInfo();
            self::$_route = compact('module', 'controller', 'action');
		}

		if (isset($name)) {
			return isset(self::$_route[$name]) ? self::$_route[$name] : null;
		}

		return self::$_route;
	}

    /**
     * 设置路由
     * @param $route
     */
	public static function setRoute($route)
    {
        if (!self::$_route) {
            self::$_route = $route;
        }
    }

	/**
	 * 解析路由字符串
	 * @param string|array $route
	 * @return array
	 */
	public static function parseRoute($route)
	{
		if (is_string($route)) {
			$routeData = explode(
				OC_DIR_SEP,
				trim(str_replace(DIRECTORY_SEPARATOR, OC_DIR_SEP, $route), OC_DIR_SEP)
			);
		} elseif (is_array($route)) {
			$routeData = array_values($route);
		} else {
			return array();
		}

		switch (count($routeData)) {
			case 2:
				list($controller, $action) = $routeData;
				if ($route{0} != OC_DIR_SEP && isset(self::$_route['module'])) {
					$module = self::$_route['module'];
				}  else {
					$module = OC_EMPTY;
				}
				break;
			case 3:
				list($module, $controller, $action) = $routeData;
				break;
			default:
				return array();
		}

		return compact('module', 'controller', 'action');
	}

	/**
	 * 获取默认服务提供者
	 */
	public static function services()
	{
		return self::$_services;
	}

	/**
	 * 框架更新
	 * @param array $params
	 * @return bool
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
		if (is_null(self::$_info)) {
			$path = OC_SYS . 'data/framework.php';
			if (ocFileExists($path)) {
				include($path);
			}
			if (isset($FRAMEWORK_INFO) && is_array($FRAMEWORK_INFO)) {
				self::$_info = $FRAMEWORK_INFO;
			} else {
				self::$_info = array();
			}
		}

		if (isset($key)) {
			return ocGet($key, self::$_info);
		}

		return self::$_info;
	}
}
