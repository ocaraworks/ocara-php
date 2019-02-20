<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  应用生成类ApplicationGenerator
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Basis;

defined('OC_PATH') or exit('Forbidden');

use Ocara\Core\ServiceProvider;

class Application extends Basis
{
    private $_language;
    private $_bootstrap;
    private $_route;

    /**
     * 获取语言
     * @param bool $getUpdated
     * @return mixed
     */
    public function getLanguage($getUpdated = false)
    {
        if ($this->_language === null || $getUpdated) {
            $this->_language = ocService()->config->get('LANGUAGE');
        }

        return $this->_language;
    }

    /**
     * 设置默认语言
     * @param $language
     */
    public function setLanguage($language)
    {
        $this->_language = $language;
    }

    /**
     * 规定在哪个错误报告级别会显示用户定义的错误
     * @param integer $error
     * @return bool|int
     */
    public function errorReporting($error = null)
    {
        $sysModel = ocContainer()->config->get('SYS_MODEL', 'application');
        $error = $error ? : ($sysModel == 'develop' ? E_ALL : 0);

        set_error_handler(
            array(ocContainer()->exceptionHandler, 'errorHandler'),
            $error
        );

        return $error;
    }

    /**
     * 获取或设置启动器
     * @param null $bootstrap
     * @return mixed
     * @throws \Ocara\Exceptions\Exception
     */
    public function bootstrap($bootstrap = null)
    {
        if (func_num_args()) {
            //initialize global config
            ocContainer()->config->loadGlobalConfig();
            error_reporting($this->errorReporting());

            //set module path
            if (OC_MODULE_PATH) {
                ocContainer()->path->setMap('modules', OC_MODULE_PATH);
                ocContainer()->loader->registerNamespace('app\modules\\', OC_MODULE_PATH);
            }

            //initialize default service provider
            $providerClass = ocConfig('DEFAULT_PROVIDER', 'Ocara\Providers\Main');
            $provider = new $providerClass(array(), ocContainer());

            ServiceProvider::setDefault($provider);
            ocImport(array(OC_SYS . 'const/config.php'));

            $bootstrap = $bootstrap ? : '\Ocara\Core\Bootstraps\Common';
            $this->_bootstrap = new $bootstrap();
            $this->_bootstrap->init();
        }

        return $this->_bootstrap;
    }

    /**
     * 解析路由
     * @return array
     */
    public function parseRoute()
    {
        if (!OC_INVOKE) {
            $_GET = ocService()->url->parseGet();
        }

        list($module, $controller, $action) = ocService()
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
            return isset($this->_route[$name]) ? $this->_route[$name] : null;
        }

        return $this->_route;
    }

    /**
     * 设置路由
     * @param $route
     */
    public function setRoute($route)
    {
        if (empty($route['module'])){
            $route['module'] = 'index';
        }

        $this->_route = $route;
    }

    /**
     * 格式化路由字符串
     * @param string|array $route
     * @return array
     */
    public function formatRoute($route)
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
                if ($route{0} != OC_DIR_SEP && isset($this->_route['module'])) {
                    $module = $this->_route['module'];
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

        $result = compact('module', 'controller', 'action');
        return $result;
    }
}