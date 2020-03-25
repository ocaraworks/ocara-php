<?php
/**
 * Restful控制器特性类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Controllers\Feature;

use Ocara\Interfaces\Feature;
use Ocara\Exceptions\Exception;

class Rest extends Base implements Feature
{
    /**
     * 获取路由
     * @param $module
     * @param $controller
     * @param array $get
     * @param array $last
     * @return array|mixed
     * @throws Exception
     */
    public function getRoute($module, $controller, array $get, $last = array())
    {
        $idParam = ocConfig(array('CONTROLLERS', 'rest', 'id_param'), 'id');
        $method = ocService()->request->getMethod();
        $idData = array();

        if (ocService()->url->isVirtualUrl(OC_URL_ROUTE_TYPE)) {
            if ($get) {
                $remainGet = array_splice($get, 1);
                $idData = array($idParam => reset($get));
                $_GET = array_merge($idData, $this->formatGet($remainGet, $last));
            } else {
                $_GET = $this->formatGet(array(), $last);
            }
        } else {
            if (array_key_exists($idParam, $get)) {
                $idData = array($idParam => $get[$idParam]);
            }
        }

        if ($idData) {
            $method = $method . OC_DIR_SEP . $idParam;
        }

        $action = ocConfig(array('CONTROLLERS', 'rest', 'action_map', $method), null);

        if (empty($action)) {
            ocService()->error->show('fault_restful_format');
        }

        $route = array($module, $controller, $action);
        return $route;
    }
}