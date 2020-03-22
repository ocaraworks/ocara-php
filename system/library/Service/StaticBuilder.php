<?php
/**
 * Ocara开源框架 静态生成插件类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Service;

use Ocara\Core\ServiceBase;

class StaticBuilder extends ServiceBase
{
    private $dir;

    /**
     * 析构函数
     */
    public function __construct()
    {
        if (!ocService()->staticPath->isOpen()) {
            $this->showError('not_exists_open_config');
        }

        $this->dir = ocPath('static');
    }

    /**
     * 全部静态生成
     * @param $callback
     */
    public function genAll($callback)
    {
        $params = ocService()->staticPath->params;

        if (empty($params)) return;

        foreach ($params as $module => $controller) {
            if (!is_array($controller)) continue;
            foreach ($controller as $action => $config) {
                if (is_array($config)) {
                    foreach ($config as $key => $value) {
                        $this->genHtml($value, $module, $action, $key, $callback);
                    }
                } elseif (is_string($config)) {
                    $this->genHtml($config, false, $module, $action, $callback);
                }
            }
        }
    }

    /**
     * 按动作action生成HTML
     * @param $route
     * @param $data
     */
    public function genAction($route, $data)
    {
        extract(ocService()->app->formatRoute($route));

        foreach ($data as $row) {
            list($file, $param) = ocService()->staticPath->getStaticFile($module, $controller, $action, $row);
            $url = ocUrl(array($module, $controller, $action), $param, false, false, false);
            $this->createHtml($file, $url);
        }
    }

    /**
     * 生成HTML
     * @param arary $params
     * @param string $module
     * @param string $controller
     * @param string $action
     * @param string|array $callback
     */
    private function genHtml($params, $module, $controller, $action, $callback)
    {
        $args = preg_match_all('/{(\w+)}/i', $params, $mt) ? $mt[1] : array();
        $args = array($module, $controller, $action, $args);

        $data = $callback ? call_user_func_array($callback, $args) : null;
        $pathMap = ocService()->staticPath->getMvcPathMap($module, $controller, $action);

        $this->genRow($pathMap, $params, $module, $controller, $action, $data);
    }

    /**
     * 生成一行
     * @param string $mvcPathMap
     * @param array $params
     * @param string $module
     * @param string $controller
     * @param string $action
     * @param array $data
     */
    private function genRow($mvcPathMap, $params, $module, $controller, $action, $data)
    {
        $route = array($module, $controller, $action);

        if ($data) {
            foreach ($data as $row) {
                if (is_array($row) && $row) {
                    $paramsPathMap = ocService()->staticPath->getParamsPathMap(
                        $params, $module, $controller, $action, $row
                    );
                    list($file, $param) = $paramsPathMap;
                    if ($file = str_ireplace('{params}', $file, $mvcPathMap)) {
                        $url = ocUrl($route, $param, false, false, false);
                        $this->createHtml($file, $url);
                    }
                }
            }
        } else {
            $url = ocUrl($route, array(), false, false, false);
            $file = trim(str_ireplace('{params}', OC_EMPTY, $mvcPathMap), OC_DIR_SEP);
            $file = $file . '.' . ocService()->staticPath->fileType;
            $this->createHtml($file, $url);
        }
    }

    /**
     * 单页面生成
     * @param string $file
     * @param string $url
     * @return bool|int
     */
    private function createHtml($file, $url)
    {
        if ($file && $url) {
            $path = $file ? $this->dir . $file : false;

            if ($content = ocRemote($url)) {
                return ocWrite($path, $content);
            }
        }

        return false;
    }
}
