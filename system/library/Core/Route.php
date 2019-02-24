<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   路由处理类Route
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Base;

defined('OC_PATH') or exit('Forbidden!');

class Route extends Base
{
    /**
     * 解析路由
     */
    public function parseRouteInfo()
    {
        $get = $_GET;
        $module = array_shift($get);
        $uModule = ucfirst($module);
        $moduleClass = OC_EMPTY;
        $controller = OC_EMPTY;

        if ($uModule) {
            $moduleNamespace = OC_MODULE_NAMESPACE ? ocNamespace(OC_MODULE_NAMESPACE): 'app\modules\\';
            $moduleClass = sprintf($moduleNamespace .'%s\controller\%sModule', $module, ucfirst($module));
            if (ocClassExists($moduleClass)) {
                $controller = $get ? array_shift($get) : null;
            } else {
                $controller = $module;
                $module = null;
            }
        }

        if (empty($module)) {
            $moduleClass = 'app\controller\\' . 'Module';
        }

        $providerType = $moduleClass::providerType();
        $featureClass = self::getFeatureClass($providerType);

        if (!ocClassExists($featureClass)) {
            ocService()->error->show('not_exists_class', $featureClass);
        }

        $feature = new $featureClass();
        $route = $feature->getRoute($module, $controller, $get);

        if (ocService()->url->isVirtualUrl(OC_URL_ROUTE_TYPE)) {
            $_GET = $this->formatGet($_GET);
        }

        return $route;
    }

    /**
     * 获取提供器特性类
     * @param $providerType
     * @return string
     * @throws \Ocara\Exceptions\Exception
     */
    public static function getFeatureClass($providerType)
    {
        $features = ocConfig(array('ROUTE', 'features'), array());

        if (isset($features[$providerType])) {
            $class = $features[$providerType];
        } else {
            $class = "\\Ocara\\Controllers\\Feature\\{$providerType}";
        }

        return $class;
    }

    /**
     * 获取提供器类
     * @param $providerType
     * @return string
     * @throws \Ocara\Exceptions\Exception
     */
    public static function getProviderClass($providerType)
    {
        $providers = ocConfig(array('ROUTE', 'providers'), array());

        if (isset($providers[$providerType])) {
            $class = $providers[$providerType];
        } else {
            $class = "\\Ocara\\Controllers\\Provider\\{$providerType}";
        }

        return $class;
    }

    /**
     * 格式化GET参数
     * @param array $data
     * @return array
     */
    public function formatGet(array $data)
    {
        $last = $get = array();
        if (is_array(end($data))) {
            $last = array_pop($data);
        }
        
        ksort($data);
        $data = array_chunk($data, 2);

        foreach($data as $row) {
            if ($row[0]) {
                $get[$row[0]] = isset($row[1]) ? $row[1] : null;
            }
        }

        return $last ? $get + $last : $get;
    }
}