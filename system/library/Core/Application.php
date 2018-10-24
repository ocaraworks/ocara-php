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
     * @param $languange
     */
    public function setLanguage($languange)
    {
        $this->_language = $languange;
    }

    /**
     * 规定在哪个错误报告级别会显示用户定义的错误
     * @param integer $error
     * @return bool|int
     */
    public function errorReporting($error = null)
    {
        $sysModel = Container::getDefault()->config->get('SYS_MODEL', 'application');
        $error = $error ? : ($sysModel == 'develop' ? E_ALL : 0);

        set_error_handler(
            ocContainer()->config->get('ERROR_HANDLER.program_error', 'ocErrorHandler'),
            $error
        );

        return $error;
    }

    /**
     * 获取或设置启动器
     * @param $bootstrap
     * @return string
     */
    public function bootstrap($bootstrap = null)
    {
        if (func_num_args()) {
            //初始化全局设置
            ocService()->config->loadGlobalConfig();
            error_reporting($this->errorReporting());

            ocImport(array(
                OC_SYS . 'const/config.php',
                OC_SYS . 'functions/common.php'
            ));

            $bootstrap = $bootstrap ? : '\Ocara\Core\Bootstrap';
            $bootstrap = new $bootstrap();

            $provider = $bootstrap->getServiceProvider(ocContainer());
            ServiceProvider::setDefault($provider);
            $this->_bootstrap = $bootstrap;
            $this->_bootstrap->init();
        }

        return $this->_bootstrap;
    }

    /**
     * 获取路由信息
     * @param string $name
     * @return array|null
     */
    public function getRoute($name = null)
    {
        if (!$this->_route) {
            if (!OC_INVOKE) {
                $_GET = ocService()->url->parseGet();
            }
            list($module, $controller, $action) = ocService()->route->parseRouteInfo();
            $this->_route = compact('module', 'controller', 'action');
        }

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
        if (!$this->_route) {
            $this->_route = $route;
        }
    }

    /**
     * 解析路由字符串
     * @param string|array $route
     * @return array
     */
    public function parseRoute($route)
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

        return compact('module', 'controller', 'action');
    }
}