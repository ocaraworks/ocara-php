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
     * @param bool $return
     * @param array $params
     */
    public static function dispatch($route, $return = false, array $params = array())
    {
        extract($route);

        if (empty($controller) || empty($action)) {
            ocService()->error->show("MVC Route Error!");
        }

        list($uModule, $uController, $uAction) = array_values(array_map('ucfirst', $route));

        $moduleDir = OC_COMMAND_MODULE ? 'commands' : 'modules';
        $modulePath = ocPath($moduleDir, $module);
        $controlPath = $modulePath . "/controllers/{$controller}/";
        $moduleNamespace = OC_NS_SEP . ocNamespace(array('app\modules', $module));
        $controlNamespace = OC_NS_SEP . ocNamespace(array('app\modules', $module, $controller));

        if (!class_exists($moduleNamespace . $uModule . '\Module', false)) {
            self::loadRoute($modulePath, $uModule, $moduleNamespace, 'Module');
        }

        self::loadRoute($controlPath, $uController, $controlNamespace, 'Controller');
        $controlClass = $controlNamespace . 'Controller';
        $method = $action . 'Action';

        if (!method_exists($controlClass, $method)) {
            $actionPath = $controlPath . "{$uAction}Action.php";
            if (ocFileExists($actionPath)) {
                include_once ($actionPath);
                $actionClass = $controlNamespace . $uAction . 'Action';
                if (class_exists($actionClass, false)) {
                    $controlClass = $actionClass;
                    $method = '_action';
                }
            }
        }

        ocContainer()->bindSingleton('action', $controlClass);

        $service = ocService();
        $service->config->loadModuleConfig($route);
        $service->lang->loadModuleConfig($route);
        $Control = ocContainer()->action;

        if ($method != '_action' && !method_exists($Control, $method)) {
            $service->error->show('no_special_class', array('Action', $uAction . 'Action'));
        }

        $Control->doAction($method);
    }

    /**
     * MVC文件和类检测
     * @param string $root
     * @param string $target
     * @param string $namespace
     * @param string $type
     * @param bool $required
     * @return bool
     * @throws Exception\Exception
     */
    public static function loadRoute($root, $target, $namespace, $type = null, $required = true)
    {
        $path = ocDir($root) . $type . '.php';

        if (!ocFileExists($path)) {
            if ($required) {
                ocService()->error->show('no_special_file_' . lcfirst($type), array(lcfirst($target)));
            }
            return false;
        }

        include_once ($path);
        if (!class_exists($namespace . $type,  false)) {
            if ($required) {
                ocService()->error->show('no_special_' . lcfirst($type), array(lcfirst($target)));
            }
            return false;
        }

        return true;
    }
}