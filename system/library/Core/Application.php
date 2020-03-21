<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  应用生成类ApplicationGenerator
 * @Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Core;

defined('OC_PATH') or exit('Forbidden');

use Ocara\Exceptions\Exception;
use Ocara\Interfaces\Bootstrap;

class Application extends Base
{
    private $language;
    private $bootstrap;
    private $route = array();

    /**
     * 启动前
     * @throws Exception
     */
    public function __construct()
    {
        $container = ocContainer();

        //initialize global config
        $container->config->loadGlobalConfig();
        $this->setLanguage($container->config->get('LANGUAGE', 'zh_cn'));

        //get environment
        $container->config->getEnvironment();
        $container->config->loadEnvironmentConfig();

        //error report
        error_reporting($this->errorReporting());

        //append module namespace
        if (OC_MODULE_NAMESPACE && OC_MODULE_PATH) {
            $container->loader->registerNamespace(OC_MODULE_NAMESPACE, OC_MODULE_PATH);
        }

        //initialize default service provider
        $providerClass = ocConfig('DEFAULT_PROVIDER', 'Ocara\Providers\Main');
        $provider = new $providerClass(array(), $container);

        ServiceProvider::setDefault($provider);

        //exception handler
        register_shutdown_function("ocShutdownHandle");
        set_exception_handler(array($container->exceptionHandler, 'exceptionHandle'));

        ocImport(array(OC_SYS . 'const/config.php'));
    }

    /**
     * 获取语言
     * @param bool $getUpdated
     * @return mixed
     */
    public function getLanguage($getUpdated = false)
    {
        if ($this->language === null || $getUpdated) {
            $this->language = ocService()->config->get('LANGUAGE');
        }

        return $this->language;
    }

    /**
     * 设置默认语言
     * @param $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * 规定在哪个错误报告级别会显示用户定义的错误
     * @param integer $error
     * @return bool|int
     */
    public function errorReporting($error = null)
    {
        $container = ocContainer();
        $sysModel = $container->config->get('SYSTEM_RUN_MODE', 'application');
        $error = $error ?: ($sysModel == 'develop' ? E_ALL : 0);

        set_error_handler(
            array($container->exceptionHandler, 'errorHandle'),
            $error
        );

        return $error;
    }

    /**
     * 获取或设置启动器
     * @param string $bootstrap
     * @return Bootstrap
     */
    public function bootstrap($bootstrap = null)
    {
        if (func_num_args()) {
            $bootstrap = $bootstrap ?: 'Ocara\Bootstraps\Common';
            $this->bootstrap = new $bootstrap();
            $this->bootstrap->init();
        }

        return $this->bootstrap;
    }

    /**
     * 执行控制器路由
     * @param $route
     * @param array $params
     * @param null $moduleNamespace
     * @return mixed
     * @throws Exception
     */
    public function run($route, $params = array(), $moduleNamespace = null)
    {
        $newRoute = $this->loadRouteConfig($this->formatRoute($route));
        $result = $this->bootstrap->start($newRoute, $params, $moduleNamespace);
        return $result;
    }

    /**
     * 执行测试
     * @param $route
     * @return mixed
     */
    public function runTest($route = array())
    {
        $newRoute = $this->formatRoute($route);
        $result = $this->bootstrap->start($newRoute);
        return $result;
    }

    /**
     * 解析路由
     * @return array
     * @throws Exception
     */
    public function parseRoute()
    {
        $service = ocService();

        if (!OC_INVOKE) {
            $_GET = $service->url->parseGet();
        }

        list($module, $controller, $action) = $service
            ->route
            ->parseRouteInfo();

        $route = compact('module', 'controller', 'action');
        return $route;
    }

    /**
     * 获取路由信息
     * @param string $name
     * @return array|null
     */
    public function getRoute($name = null)
    {
        if (isset($name)) {
            return isset($this->route[$name]) ? $this->route[$name] : null;
        }

        return $this->route ?: array();
    }

    /**
     * 设置路由
     * @param $route
     */
    public function setRoute($route)
    {
        $this->route = $route ?: array();
    }

    /**
     * 格式化路由字符串
     * @param string|array $route
     * @return array
     */
    public function formatRoute($route)
    {
        $isModule = false;

        if (is_string($route)) {
            if ($route{0} == OC_DIR_SEP) $isModule = true;
            $routeStr = trim(ocCommPath($route), OC_DIR_SEP);
            $routeData = explode(OC_DIR_SEP, $routeStr);
        } elseif (is_array($route)) {
            $routeData = array_values($route);
        } else {
            return array();
        }

        switch (count($routeData)) {
            case 2:
                list($controller, $action) = $routeData;
                if (!$isModule && isset($this->route['module'])) {
                    $module = $this->route['module'];
                } else {
                    $module = OC_EMPTY;
                }
                break;
            case 3:
                list($module, $controller, $action) = $routeData;
                break;
            case 1:
                if ($isModule) {
                    $module = $routeData[0];
                    $controller = OC_EMPTY;
                    $action = OC_EMPTY;
                } else {
                    $module = OC_EMPTY;
                    $controller = !empty($this->route['controller']) ? $this->route['controller'] : OC_EMPTY;
                    $action = $routeData[0];
                }
                break;
            default:
                return array();
        }

        $result = compact('module', 'controller', 'action');
        return $result;
    }

    /**
     * 加载路由配置
     * @param array $route
     * @return mixed
     * @throws Exception
     */
    public function loadRouteConfig(array $route)
    {
        $service = ocService();

        if (empty($route['module'])) {
            $route['module'] = OC_DEFAULT_MODULE;
        }

        $service->config->loadModuleConfig($route);
        $service->lang->loadModuleConfig($route);

        if (empty($route['controller'])) {
            $route['controller'] = ocConfig('DEFAULT_CONTROLLER');
        }

        if (empty($route['action'])) {
            $route['action'] = ocConfig('DEFAULT_ACTION');
        }

        $service->app->setRoute($route);
        $service->config->loadControllerConfig($route);
        $service->config->loadActionConfig($route);

        $service->lang->loadControllerConfig($route);
        $service->lang->loadActionConfig($route);

        return $route;
    }
}