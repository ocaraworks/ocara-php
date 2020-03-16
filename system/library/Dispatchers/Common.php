<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Dispatcher路由分发器类
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Dispatchers;

use Ocara\Core\Base;
use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class Common extends Base
{
    const EVENT_BEFORE_DISPATCH = 'beforeDispatch';
    const EVENT_AFTER_DISPATCH = 'afterDispatch';

    /**
     * 注册事件
     * @throws Exception
     */
    public function registerEvents()
    {
        $this->event(self::EVENT_BEFORE_DISPATCH)
            ->append(ocConfig(array('EVENTS', 'dispatch', 'before_dispatch'), null));

        $this->event(self::EVENT_AFTER_DISPATCH)
            ->append(ocConfig(array('EVENTS', 'dispatch', 'after_dispatch'), null));
    }

    /**
     * 分发路由控制器
     * @param $route
     * @param null $moduleNamespace
     * @param array $params
     */
    public function dispatch($route, $moduleNamespace = null, $params = array())
    {
        $service = ocService();
        $moduleNamespace = ocNamespace($moduleNamespace ?: 'app\modules');

        if (empty($route['controller']) || empty($route['action'])) {
            $service->error->show('null_route');
        }

        $uController = ucfirst($route['controller']);
        $uAction = ucfirst($route['action']);

        $this->fire(self::EVENT_BEFORE_DISPATCH, array($route));

        if ($route['module']) {
            $cNamespace = sprintf($moduleNamespace . '%s\%s\%s\\',
                $route['module'],
                'controller',
                $route['controller']
            );
        } else {
            $cNamespace = sprintf('app\controller\%s\\', $route['controller']);
        }

        $controllerClass = $cNamespace . 'Controller';
        $method = $route['action'] . 'Action';
        $isActionClass = false;

        if (!class_exists($controllerClass)) {
            $service->error->show('no_controller', array($controllerClass));
        }

        if (!method_exists($controllerClass, $method)) {
            $actionClass = $cNamespace . $uAction . 'Action';
            if (class_exists($actionClass)) {
                $controllerClass = $actionClass;
                $method = '__action';
                $isActionClass = true;
            }
        }

        $params['route'] = $route;
        $Control = new $controllerClass($params);
        $Control->initialize($isActionClass);

        if (!method_exists($Control, $method)) {
            $service->error->show('no_special_action', array('Action', $uAction . 'Action'));
        }

        $Control->doAction($method);

        $this->fire(self::EVENT_AFTER_DISPATCH, array($route));
    }
}