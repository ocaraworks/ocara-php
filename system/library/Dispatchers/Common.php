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

defined('OC_PATH') or exit('Forbidden!');

class Common extends Base
{
    /**
     * 分发路由控制器
     * @param $route
     * @throws \Ocara\Exceptions\Exception
     */
    public function dispatch($route)
    {
        $uController = ucfirst($route['controller']);
        $uAction = ucfirst($route['action']);

        if (empty($route['controller']) || empty($route['action'])) {
            ocService()->error->show("MVC Route Error!");
        }

        $controllerDir = OC_CONSOLE_MODULE ? 'console' : 'controller';

        if ($route['module']) {
            $cNamespace = sprintf('app\modules\%s\%s\%s\\',
                $route['module'],
                $controllerDir,
                $route['controller']
            );
        } else {
            $cNamespace = sprintf('app\%s\%s\\', $controllerDir, $route['controller']);
        }

        $cClass = $cNamespace . $uController . 'Controller';
        $method = $route['action'] . 'Action';

        if (!class_exists($cClass)) {
            ocService()->error->show('no_controller', array($uController . 'Controller'));
        }

        if (!method_exists($cClass, $method)) {
            $aClass = $cNamespace . 'actions\\' . $uAction . 'Action';
            if (class_exists($aClass)) {
                $cClass = $aClass;
                $method = '_action';
            }
        }

        ocContainer()->bindSingleton('controller', $cClass);

        $service = ocService();
        $service->config->loadModuleConfig($route);
        $service->lang->loadModuleConfig($route);
        $Control = ocContainer()->controller;

        if (!method_exists($Control, $method)) {
            $service->error->show('no_special_action', array('Action', $uAction . 'Action'));
        }

        $Control->doAction($method);
    }
}