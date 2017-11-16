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
        $module = ocGet(0, $_GET);
        $controller = $action = null;
        $isModule = false;

        $controllerNamespace = 'Controller' . OC_NS_SEP;
        $controllerClass = OC_EMPTY;
        $appDir = OC_APPLICATION_PATH . 'controller/';
        $isStandard = false;

        if (isset($_GET[1])) {
            $controller = ($c = ocGet(1, $_GET)) ? $c: null;
            $param2 = ocGet(2, $_GET);
            $action = $param2 && !is_array($param2) ? $param2 : null;
            $ucontroller = ucfirst($controller);
            if ($module && $module != OC_DEV_SIGN) {
                $umodule = ucfirst($module);
                self::loadRoute(
                    $appDir . $umodule,
                    $umodule,
                    $controllerNamespace . $umodule . OC_NS_SEP,
                    'Module'
                );
                $controllerClass = $controllerNamespace . $umodule . OC_NS_SEP . $umodule . 'Module';
            } elseif($controller && self::_checkRoute($appDir, $ucontroller, true)) {
                $controllerNamespace = $controllerNamespace . $ucontroller . OC_NS_SEP;
                $controllerClass = $controllerNamespace . $ucontroller . 'Controller';
                $dir = $appDir . $ucontroller . OC_DIR_SEP;
                $className = $ucontroller . 'Module';
                if (self::_checkRoute($dir, $className, false)) {
                    include_once($dir . $className . '.php');
                    $moduleClass = $controllerNamespace . $ucontroller . 'Module';
                    if (class_exists($moduleClass, false)) {
                        $module = $controller;
                        $controller = $action;
                        $controllerClass = $moduleClass;
                        $isModule = true;
                    }
                }
                $isStandard = true;
            }
        }

        $featureClass = 'Ocara\Feature\Common';
        if ($module != OC_DEV_SIGN) {
            if (empty($controller)) {
                $controller = ocConfig('DEFAULT_CONTROLLER', 'home');
                $ucontroller = ucfirst($controller);
                $controllerClass = $controllerNamespace
                    . $ucontroller
                    . OC_NS_SEP
                    . $ucontroller
                    . 'Controller';
            }
            $featureClass = self::getControllerFeature($controllerClass);
            $action = call_user_func_array(
                array($featureClass, 'getControllerAction'), array($action, $isModule, $isStandard)
            );
        }

        $route = call_user_func_array(
            array($featureClass, 'getDefaultRoute'), array($module, $controller, $action)
        );

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


    /**
     * 获取控制器特性类
     * @param $class
     * @return string
     * @throws Exception\Exception
     */
    public static function getControllerFeature($class)
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
     * 检测路由
     * @param string $path
     * @param string $name
     * @param bool $isDir
     * @return bool|mixed|string
     */
    private static function _checkRoute($path, $name, $isDir)
    {
        $path = $path . $name;
        if ($isDir === true) {
            return is_dir($path);
        } else {
            $path = $name ? $path . '.php' : $path;
            return ocFileExists($path);
        }
    }
}