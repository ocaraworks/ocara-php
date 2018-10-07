<?php
namespace Ocara\Controllers\Feature;

use Ocara\Ocara;
use Ocara\Interfaces\Feature;
use Ocara\Controllers\Feature\Base;

defined('OC_PATH') or exit('Forbidden!');

final class Rest extends Base implements Feature
{
    /**
     * 获取路由
     * @param array $get
     * @return null
     */
    public function getAction(array $get)
    {
        $id = null;
        $idParam = ocConfig('CONTROLLERS.rest.id_param', 'id');

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

        $action = ocConfig('CONTROLLERS.rest.action_map.' . $method, null);
        if (empty($action)) {
            ocService()->error->show('fault_url');
        }

        $_GET = array_values($get);

        return null;
    }
}