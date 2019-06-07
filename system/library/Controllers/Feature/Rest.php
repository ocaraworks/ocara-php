<?php
namespace Ocara\Controllers\Feature;

use Ocara\Interfaces\Feature;
use Ocara\Controllers\Feature\Base;
use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class Rest extends Base implements Feature
{
    /**
     * 获取路由
     * @param string $module
     * @param string $controller
     * @param array $get
     * @return array|mixed
     * @throws Exception
     */
    public function getRoute($module, $controller, array $get)
    {
        $id = null;
        $idParam = ocConfig(array('CONTROLLERS', 'rest', 'id_param'), 'id');

        if (ocService()->url->isVirtualUrl(OC_URL_ROUTE_TYPE)) {
            $count = count($get);
            $end = end($get);
            if ($count == 1 && !is_array($end) || $count == 2 && is_array($end)) {
                $id = array_shift($get);
            }
        } else {
            if (array_key_exists($idParam, $get)) {
                $id = ocService()->request->getGet($idParam);
            }
        }

        $method = ocService()->request->getMethod();
        if (!ocEmpty($id)) {
            $method = $method . '/id';
            $_GET[$idParam] = $id;
        }

        $action = ocConfig(array('CONTROLLERS', 'rest', 'action_map', $method), null);

        if (ocService()->url->isVirtualUrl(OC_URL_ROUTE_TYPE)) {
            $_GET = array_values($get);
        } else {
            $_GET = $get;
        }

        $route = array($module, $controller, $action);
        return $route;
    }
}