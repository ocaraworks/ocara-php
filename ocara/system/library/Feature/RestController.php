<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Restful控制器特性类Common
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Feature;
use Ocara\Interfaces\Feature;
use Ocara\Container;
use Ocara\Error;
use Ocara\Request;
use Ocara\Validator;

defined('OC_PATH') or exit('Forbidden!');

final class RestController extends FeatureBase implements Feature
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
            ocDel($_GET, 0, 1);
        }
        return null;
    }

    /**
     * 设置最终路由
     * @param $module
     * @param $controller
     * @param $action
     */
    public static function getDefaultRoute($module, $controller, $action)
    {
        $idParam = ocConfig('CONTROLLERS.rest.id_param', 'id');
        $method = Request::getMethod();

        if (Request::getGet()) {
            $id = key($_GET);
            array_shift($_GET);
            $_GET[$idParam] = $id;
            $method = $method . '/id';
        }

        $action = strtr($method, ocConfig('CONTROLLERS.rest.action_map'));
        if (empty($action)) {
            Error::show('ROUTE_ERROR');
        }

        return array($module, $controller, $action);
    }
}