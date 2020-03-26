<?php
/**
 * 路由处理类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use \ReflectionException;
use Ocara\Exceptions\Exception;

class Route extends Base
{
    /**
     * 事件常量
     */
    const EVENT_AFTER_GET_ROUTE = 'afterGetRoute';

    /**
     * 事件注册
     * @throws Exception
     */
    public function registerEvents()
    {
        $this->event(self::EVENT_AFTER_GET_ROUTE)
            ->resource()
            ->append(ocConfig('RESOURCE.url.after_get_route', 'Ocara\Handlers\RouteHandler'));
    }

    /**
     * 解析路由
     * @return mixed
     * @throws Exception
     * @throws ReflectionException
     */
    public function parseRouteInfo()
    {
        $get = $_GET;
        $moduleClass = OC_EMPTY;
        $controller = OC_EMPTY;

        $last = array();
        if (is_array(end($get))) {
            $last = array_pop($get);
        }

        $service = ocService();
        $module = array_shift($get);
        $uModule = ucfirst($module);

        if ($uModule) {
            $moduleNamespace = OC_MODULE_NAMESPACE ? ocNamespace(OC_MODULE_NAMESPACE) : 'app\modules\\';
            $moduleClass = sprintf($moduleNamespace . '%s\controller\%sModule', $module, ucfirst($module));
            if (ocClassExists($moduleClass)) {
                $controller = $get ? array_shift($get) : null;
            } else {
                $controller = $module;
                $module = null;
            }
        }

        if (empty($module)) {
            if ($controller && (!defined('OC_ALLOW_GLOBAL_ROUTE') || !OC_ALLOW_GLOBAL_ROUTE) && OC_MODULE_NAMESPACE) {
                $service->error->show('need_module');
            }
            $moduleClass = 'app\controller\\' . 'Module';
        }

        $controllerType = $moduleClass::controllerType();
        $featureClass = ControllerBase::getFeatureClass($controllerType);

        if (!ocClassExists($featureClass)) {
            $service->error->show('not_exists_class', array($featureClass));
        }

        $feature = new $featureClass();

        if ($service->url->isVirtualUrl(OC_URL_ROUTE_TYPE)) {
            $route = $feature->getRoute($module, $controller, $get, $last);
        } else {
            $route = $feature->getRoute($module, $controller, $get);
        }

        if ($this->event(self::EVENT_AFTER_GET_ROUTE)->has()) {
            $_GET = $this->fire(self::EVENT_AFTER_GET_ROUTE, array($route, $_GET));
        }

        if (PHP_SAPI == 'cli') {
            if (ocService()->request->isPost()) {
                $_POST = $_GET;
                $_GET = array();
            }
        }

        return $route;
    }
}