<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   路由处理类Route
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Base;

defined('OC_PATH') or exit('Forbidden!');

class Route extends Base
{
    /**
     * 解析路由
     */
    public function parseRouteInfo()
    {
        $get     = $_GET;
        $module  = array_shift($get);
        $uModule = ucfirst($module);

        if ($module == OC_DEV_SIGN) return $this->getDevelopRoute($get);

        if ($uModule) {
            $moduleClass = 'modules' . OC_NS_SEP . $module . OC_NS_SEP. 'Module';
            if (ocClassExists($moduleClass)) {
                $controller = $get ? array_shift($get) : null;
            } else {
                $controller = $module;
                $module = null;
            }
        }

        if (empty($module)) {
            $module = 'index';
        }

        if (empty($controller)) {
            $controller = ocConfig('DEFAULT_CONTROLLER');
        }

        $controllerType = self::getControllerType($module, $controller);
        $routeClass = "\\Ocara\\Controllers\\Feature\\{$controllerType}";
        $routeFeature = new $routeClass();
        $action       = $routeFeature->getAction($get);
        $route        = $routeFeature->getLastRoute($module, $controller, $action);

        if (ocService()->url->isVirtualUrl(OC_URL_ROUTE_TYPE)) {
            $_GET = $this->formatGet($_GET);
        }

        return $route;
    }

    /**
     * 获取控制器类型
     * @param $module
     * @param $controller
     * @return string
     */
    public static function getControllerType($module, $controller)
    {
        $route = ltrim(implode('/', array($module, $controller)), '/');
        $isRestful = in_array($route, ocConfig(array('ROUTE', 'resource'), array()));

        if ($isRestful) {
            $controllerType = 'Rest';
        } else {
            $controllerType = 'Common';
        }

        return $controllerType;
    }

    /**
     * 获取开发者中心路由
     * @param $get
     * @return array
     */
    public function getDevelopRoute($get)
    {
        $controller = array_shift($get);
        $action = array_shift($get);
        $route = array(OC_DEV_SIGN, $controller, $action);
        return $route;
    }

    /**
     * 格式化GET参数
     * @param array $data
     * @return array
     */
    public function formatGet(array $data)
    {
        $last = $get = array();
        if (is_array(end($data))) {
            $last = array_pop($data);
        }
        
        ksort($data);
        $data = array_chunk($data, 2);

        foreach($data as $row) {
            if ($row[0]) {
                $get[$row[0]] = isset($row[1]) ? $row[1] : null;
            }
        }
        return $last ? $get + $last : $get;
    }
}