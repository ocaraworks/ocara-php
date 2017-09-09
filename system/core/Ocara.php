<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  框架引导类Ocara
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_EXECUTE_STATR_TIME') OR define('OC_EXECUTE_STATR_TIME', microtime(true));

defined('OC_PATH') OR define(
	'OC_PATH', str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname(dirname(__DIR__)))) . '/'
);

require_once (OC_PATH . 'system/functions/utility.php');
require_once (OC_PATH . 'system/const/basic.php');
require_once (OC_SYS . 'core/Base.php');
require_once (OC_CORE . 'Config.php');
require_once (OC_CORE . 'Container.php');

final class Ocara
{
	/**
	 * @var $OC_CONF 	框架信息
	 * @var $CONF 		公共配置 
	 * @var $OC_LANG    框架语言数据
	 */
	private static $_container;
	private static $_instance;
	private static $_frameworkInfo;

	private static $_language = array();
	private static $_route    = array();

	private function __clone(){}
	private function __construct(){}

	/**
	 * 单例模式引用
	 */
	public static function getInstance()
	{
		if (self::$_instance === null) {
			self::$_instance = new self();
			self::initialize();
		}
		return self::$_instance;
	}

	/**
	 * 运行框架
	 */
	public static function run($bootstrap = null)
	{
		self::getInstance();

		$bootstrap = $bootstrap ? $bootstrap : '\Ocara\Bootstrap';
		$bootstrap = new $bootstrap();
		$bootstrap->initialize();

		self::$_route = $bootstrap->getRouteInfo();
		define('OC_MODULE_URL', OC_ROOT_URL . ocDir(self::$_route['module']));

		$bootstrap->run(self::$_route);
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
	 * 启动控制器
	 * @param array|string $route
	 * @param bool $return
	 * @param array $params
	 */
	public static function boot($route, $return = false, array $params = array())
	{
		extract($route);

		if (empty($controller) || empty($action)) {
			Error::show("MVC Route Error!");
		}

		list($umodule, $ucontroller, $uaction) = array_values(array_map('ucfirst', $route));
		$modulePath = OC_APPLICATION_PATH . 'controller/' . $umodule;
		$controllerPath = $modulePath . "/{$ucontroller}/";
		$controllerNamespace = ocNamespace(array('Controller', $umodule, $ucontroller));
		$moduleNamespace = ocNamespace(array('Controller', $umodule));

		if ($umodule && !class_exists($moduleNamespace . $umodule . 'Module', false)) {
			self::$_container->route->loadRoute($modulePath, $umodule, $moduleNamespace, 'Module');
		}

		self::$_container->route->loadRoute($controllerPath, $ucontroller, $controllerNamespace, 'Controller');
		$controlClass = $controllerNamespace . $ucontroller . 'Controller';
		$method = $action . 'Action';

		if (!method_exists($controlClass, $method)
			&& ocFileExists($actionPath = $controllerPath . "Action/{$uaction}Action.php")
		) {
			include_once ($actionPath);
			$actionClass = $controllerNamespace . 'Action' . OC_NS_SEP . $uaction . 'Action';
			if (class_exists($actionClass, false)) {
				$controlClass = $actionClass;
				$method = '_action';
			}
		}

		Config::loadApplicationConfig('conf', 'control');

		$Control = new $controlClass();
		if ($method != '_action' && !method_exists($Control, $method)) {
			Error::show('no_special_class', array('Action', $uaction));
		}

		$Control->initialize($route);
		if ($return) {
			$Control->checkForm(false);
			return $Control->doReturnAction($method, $params);
		} else {
			$Control->doAction($method);
		}
	}

	/**
	 * 当前权限检测
	 * @param array $route
	 */
	public static function checkRouteAccess(array $route)
	{
		$route = array_values($route);
		$callback = ocConfig('CALLBACK.auth.check', false);
		if ($callback) {
			self::accessResult(Call::run($callback, $route), $route);
		}
	}

	/**
	 * 初始化设置
	 */
	public static function initialize()
	{
		@ini_set('register_globals', 'Off');
		register_shutdown_function("ocShutdownHandler");

		Config::getInstance();
		define('OC_SYS_MODEL', ocConfig('SYS_MODEL', 'application'));
		self::$_language = ocConfig('LANGUAGE', 'zh_cn');

		error_reporting(self::errorReporting());
		set_exception_handler(
			ocConfig('ERROR_HANDLER.exception_error', 'ocExceptionHandler', true)
		);

		self::container();
		spl_autoload_register(array(__CLASS__, 'autoload'));
		Base::$container = self::$_container;

		ocImport(array(
			OC_SYS . 'const/config.php',
			OC_SYS . 'functions/common.php',
		));
	}

	/**
	 * 获取控制器特性类
	 * @param $class
	 */
	public static function getControllerFeatureClass($class)
	{
		$controller = new \ReflectionClass($class);
		$className = $controller->getName();
		$controllers = ocConfig('CONTROLLER_FEATURE_CLASS', array());

		foreach ($controllers as $name) {
			if ($controller->isSubclassOf('Ocara\\Controller\\' . $name)) {
				return 'Ocara\Feature\\' . $name;
			}
		}

		Error::show('error_class_extends', array($className, 'Controller'));
	}

	/**
	 * 规定在哪个错误报告级别会显示用户定义的错误
	 * @param integer $error
	 */
	public static function errorReporting($error = false)
	{
		$error = $error ? $error : (OC_SYS_MODEL == 'develop' ? E_ALL : 0);
		set_error_handler(
			ocConfig('ERROR_HANDLER.program_error', 'ocErrorHandler', true),
			$error
		);
		return $error;
	}

	/**
	 * 获取路由信息
	 * @param string $name
	 */
	public static function getRoute($name = false)
	{
		if (func_num_args()) {
			return isset(self::$_route[$name]) ? self::$_route[$name] : null;
		}
		return self::$_route;
	}

	/**
	 * 解析路由字符串
	 * @param string $route
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
	 * 获取当前语言
	 */
	public static function language()
	{
		return self::$_language;
	}

	/**
	 * 获取服务容器
	 */
	public static function container()
	{
		if (self::$_container === null) {
			self::$_container = new Container();
		}
		return self::$_container;
	}

	/**
	 * 权限访问检测结果
	 * @param bool $result
	 * @param array $route
	 */
	public static function accessResult($result, array $route)
	{
		$result = $result === true;
		if (!$result) {
			$callback = ocConfig('CALLBACK.auth.check', false);
			if ($callback) {
				Call::run($callback, $route);
			} else {
				Error::show('no_access');
			}
		}
	}

	/**
	 * 自动加载类
	 * @param string $class
	 * @return bool|mixed
	 * @throws Exception
	 */
	public static function autoload($class)
	{
		$newClass = trim($class, OC_NS_SEP);
		if (strstr($newClass, OC_NS_SEP)) {
			$filePath = strtr($newClass, ocConfig('AUTOLOAD_MAP'));
			if ($filePath == $class) {
				$filePath = strtr($newClass, ocConfig('APP_AUTOLOAD_MAP'));
			}
			if ($filePath == $newClass) {
				$filePath = OC_ROOT . 'service/library/' . ocCommPath($newClass);
			}
			$filePath .= '.php';
		} else {
			$filePath = OC_ROOT . 'service/library/' . $newClass . '.php';
		}

		if (ocFileExists($filePath)) {
			include_once($filePath);
			if (class_exists($newClass, false)) {
				if (method_exists($newClass, 'loadLanguage')) {
					$newClass::loadLanguage($filePath);
				}
				return true;
			}
			if (interface_exists($newClass, false)) {
				return true;
			}
		}

		$autoloads = spl_autoload_functions();
		foreach ($autoloads as $func) {
			if (is_string($func)) {
				call_user_func_array($func, array($class));
			} elseif (is_array($func)) {
				if (reset($func) === __CLASS__) continue;
				call_user_func_array($func, array($class));
			} else {
				continue;
			}
			if (class_exists($class, false) || interface_exists($newClass, false)) {
				return true;
			}
		}

		Error::show('not_exists_class', array($class));
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
	public static function getFrameworkInfo($key = null)
	{
		if (is_null(self::$_frameworkInfo)) {
			$path = OC_SYS . 'data/framework.php';
			if (ocFileExists($path)) {
				include($path);
			}
			if (isset($FRAMEWORK_INFO) && is_array($FRAMEWORK_INFO)) {
				self::$_frameworkInfo = $FRAMEWORK_INFO;
			} else {
				self::$_frameworkInfo = array();
			}
		}

		if (func_num_args()) {
			return ocGet($key, self::$_frameworkInfo);
		}

		return self::$_frameworkInfo;
	}
}
