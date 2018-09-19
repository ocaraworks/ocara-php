<?php
/**
 * Created by PhpStorm.
 * User: BORUI-DIY
 * Date: 2017/6/25 0025
 * Time: 下午 1:50
 */
namespace Ocara;

use Ocara\Base;
use Ocara\Container;

abstract class BootstrapBase extends Base
{
    /**
     * 获取默认服务提供器
     * @return string
     */
    public function getServiceProvider()
    {
        $provider = new \Ocara\Providers\Main();
        return $provider;
    }

    /**
     * 启动控制器
     * @param array|string $route
     * @param bool $return
     * @param array $params
     */
    public static function run($route, $return = false, array $params = array())
    {
        extract($route);

        if (empty($controller) || empty($action)) {
            Ocara::services()->error->show("MVC Route Error!");
        }

        list($umodule, $ucontroller, $uaction) = array_values(array_map('ucfirst', $route));
        $modulePath = OC_APPLICATION_PATH . 'controller/' . $umodule;
        $controlPath = $modulePath . "/{$ucontroller}/";
        $controlNamespace = OC_NS_SEP . ocNamespace(array('Controller', $umodule, $ucontroller));
        $moduleNamespace = OC_NS_SEP . ocNamespace(array('Controller', $umodule));

        if ($umodule && !class_exists($moduleNamespace . $umodule . 'Module', false)) {
            self::loadRoute($modulePath, $umodule, $moduleNamespace, 'Module');
        }

        self::loadRoute($controlPath, $ucontroller, $controlNamespace, 'Controller');
        $controlClass = $controlNamespace . $ucontroller . 'Controller';
        $method = $action . 'Action';

        if (!method_exists($controlClass, $method)) {
            $actionPath = $controlPath . "Action/{$uaction}Action.php";
            if (ocFileExists($actionPath)) {
                include_once ($actionPath);
                $actionClass = $controlNamespace . 'Action' . OC_NS_SEP . $uaction . 'Action';
                if (class_exists($actionClass, false)) {
                    $controlClass = $actionClass;
                    $method = '_action';
                }
            }
        }

        Ocara::services()->config->loadControlConfig($route);
        Ocara::services()->lang->loadControlLang($route);
        Container::getDefault()->bindSingleton($controlClass);

        $Control = Container::getDefault()->create($controlClass, array($route));
        if ($method != '_action' && !method_exists($Control, $method)) {
            Ocara::services()->error->show('no_special_class', array('Action', $uaction));
        }

        $Control->init($route);
        if ($return) {
            $Control->checkForm(false);
            return $Control->doReturnAction($method, $params);
        } else {
            $Control->doAction($method);
        }
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
        $path = ocDir($root) . $target . $type . '.php';

        if (!ocFileExists($path)) {
            if ($required) {
                Ocara::services()->error->show('no_special_file', array($type, $target . '.php'));
            }
            return false;
        }

        include_once ($path);
        if (!class_exists($namespace . $target . $type,  false)) {
            if ($required) {
                Ocara::services()->error->show('no_special_class', array($type, $target));
            }
            return false;
        }

        return true;
    }
}