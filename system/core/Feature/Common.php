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
use Ocara\Route;
use Ocara\Url;
use Ocara\View\Common as CommonView;

defined('OC_PATH') or exit('Forbidden!');

class Common extends FeatureBase implements Feature
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
     * @param string $module
     * @param string $controller
     * @param string $action
     * @return array
     */
    public static function getDefaultRoute($module, $controller, $action)
    {
        if (Url::isVirtualUrl(OC_URL_ROUTE_TYPE)) {
            $_GET = self::$container->route->formatGet($_GET);
        }

        if (empty($action)) {
            $action = ocConfig('DEFAULT_ACTION', 'index');
        }

        return array($module, $controller, $action);
    }

    /**
     * 获取表单令牌类
     */
    public function getFormToken()
    {
        ocImport(OC_CORE . 'FormToken.php');
        $formToken = new FormToken($this->getRoute());
        return $formToken;
    }

    /**
     * 获取View视图类
     */
    public static function getView($route)
    {
        ocImport(OC_CORE . '/View/Common.php');
        $view = new CommonView();
        $view->setRoute($route);
        $view->initialize();
        return $view;
    }
}