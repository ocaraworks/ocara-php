<?php
/**
 * Created by PhpStorm.
 * User: BORUI-DIY
 * Date: 2017/6/25 0025
 * Time: 下午 1:50
 */
namespace Ocara\Core;

use Ocara\Core\Base;
use Ocara\Core\Container;
use Ocara\Providers\Main;

abstract class BootstrapBase extends Base
{
    /**
     * 获取默认服务提供器
     * @param \Ocara\Core\Container $container
     * @return Main
     */
    public function getServiceProvider(Container $container)
    {
        $provider = new Main(array(), $container);
        return $provider;
    }

    /**
     * 分发路由控制器
     * @param array|string $route
     * @param array $params
     */
    public static function dispatch($route, array $params = array())
    {
        $uController = ucfirst($route['controller']);
        $uAction = ucfirst($route['action']);

        if (empty($route['controller']) || empty($route['action'])) {
            ocService()->error->show("MVC Route Error!");
        }

        if ($route['module']) {
            $moduleDir = OC_COMMAND_MODULE ? 'console' : 'modules';
            $cNamespace = sprintf('app\%s\%s\controller\%s\\',
                $moduleDir,
                $route['module'],
                $route['controller']
            );
        } else {
            $cNamespace = sprintf('app\controller\%s\\', $route['controller']);
        }

        $cClass = $cNamespace . $uController . 'Controller';
        $method = $route['action'] . 'Action';

        if (!class_exists($cClass)) {
            ocService()->error->show('no_special_controller', array($cClass));
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
            $service->error->show('no_special_class', array('Action', $uAction . 'Action'));
        }

        $Control->doAction($method);
    }

    /**
     * MVC类检测
     * @param $class
     * @param null $type
     * @param bool $required
     * @return bool
     */
    public static function loadRoute($class, $type = null, $required = true)
    {
        if (!class_exists($class,  false)) {
            if ($required) {
                ocService()->error->show('no_special_' . lcfirst($type), array($class));
            }
            return false;
        }

        return true;
    }
}