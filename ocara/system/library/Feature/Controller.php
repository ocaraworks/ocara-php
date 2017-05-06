<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   普通控制器特性类Common
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Feature;
use Ocara\Interfaces\Feature;
use Ocara\Container;
use Ocara\Request;
use Ocara\FormToken;
use Ocara\View;

defined('OC_PATH') or exit('Forbidden!');

class Controller extends FeatureBase implements Feature
{
     /**
     * 获取路由
     * @param string $action
     * @param bool $isModule
     * @param bool $isStandard
     * @return bool|null|string
     */
    public static function getControllerAction($action, $isModule = false, $isStandard = false)
    {
        if ($isStandard) {
            ocDel($_GET, 0, 1, 2);
        }

        if ($isModule) {
            $a = Request::getGet(3);
            if (is_array($a)) {
                $action = false;
            } else {
                $action = strtolower($a);
                ocDel($_GET, 3);
            }
        }

        return $action;
    }

    /**
     * 设置最终路由
     * @param $module
     * @param $controller
     * @param $action
     */
    public static function getDefaultRoute($module, $controller, $action)
    {
        if (empty($action)) {
            $action = ocConfig('DEFAULT_ACTION', 'index');
        }

        return array($module, $controller, $action);
    }

    /**
     * 获取表单令牌类
     */
    public function getFormToken(Container $container)
    {
        ocImport(OC_LIB . 'FormToken.php');
        $formToken = new FormToken($this->getRoute());
        return $formToken;
    }

    /**
     * 获取View模板类
     */
    public static function getView(Container $container, $route)
    {
        ocImport(OC_LIB . 'View.php');
        $view = new View();
        $view->setRoute($route);
        $view->initialize();
        return $view;
    }
}