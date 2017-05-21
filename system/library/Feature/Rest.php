<?php
namespace Ocara\Feature;
use Ocara\Interfaces\Feature;
use Ocara\Container;
use Ocara\Error;
use Ocara\Request;
use Ocara\Validator;
use Ocara\Route;
use Ocara\Url;

defined('OC_PATH') or exit('Forbidden!');

final class Rest extends FeatureBase implements Feature
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
     * @param string $module
     * @param string $controller
     * @param string $action
     */
    public static function getDefaultRoute($module, $controller, $action)
    {
        $id = null;
        $idParam = ocConfig('CONTROLLERS.rest.id_param', 'id');

        if (Url::isVirtualUrl(OC_URL_ROUTE_TYPE)) {
            $count = count($_GET);
            $end = end($_GET);
            if ($count == 1 && !is_array($end) || $count == 2 && is_array($end)) {
                $id = array_shift($_GET);
            }
            $_GET = Route::formatGet($_GET);
        } else {
            if (array_key_exists($idParam, $_GET)) {
                $id = Request::getGet($idParam);
            }
        }

        $method = Request::getMethod();
        if (!ocEmpty($id)) {
            $method = $method . '/id';
            $_GET[$idParam] = $id;
        }

        $action = ocConfig('CONTROLLERS.rest.action_map.' . $method, null);
        if (empty($action)) {
            Error::show('fault_url');
        }

        return array($module, $controller, $action);
    }
}