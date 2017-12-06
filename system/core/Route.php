<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   路由处理类Route
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

class Route extends Base
{
    /**
     * 解析路由
     */
    public function parseRouteInfo()
    {
        $get = $_GET;
        $module = array_shift($get);
        $uModule = ucfirst($module);

        if ($module == OC_DEV_SIGN) {
            return $this->getDevelopRoute($get);
        }

        if ($uModule) {
            $moduleClass = ocNamespace('Controller', $uModule) . $uModule . 'Module';
            if (class_exists($moduleClass)) {
                $controller = $get ? array_shift($get) : null;
            } else {
                $module = null;
                $controller = $module;
            }
        }

        if (empty($controller)) {
            $controller = ocConfig('DEFAULT_CONTROLLER');
        }

        $route = ltrim(implode('/', array($module, $controller)), '/');
        $isRestful = in_array($route, ocConfig(array('ROUTE', 'resource')));

        if ($isRestful) {
            $controllerType = 'Rest';
        } else {
            $controllerType = 'Common';
        }

        $featureClass = "\\Ocara\\Feature\\{$controllerType}";
        $feature = new $featureClass();
        $action = $feature->getAction($get);
        $route = $feature->getLastRoute($module, $controller, $action);

        return $route;
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
    public static function formatGet(array $data)
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