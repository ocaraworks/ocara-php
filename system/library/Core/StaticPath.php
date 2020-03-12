<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   静态路径生成类StaticPath
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Core;

use Ocara\Core\Base;
use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class StaticPath extends Base
{
    public $open;
    public $fileType;
    public $route;
    public $params;
    public $delimiter;

    /**
     * 初始化函数
     */
    public function __construct()
    {
        $this->open = ocConfig(array('STATIC', 'open'), 0);
        $this->fileType = ocConfig(array('STATIC', 'file_type'), 'html');
        $this->route = ocConfig(array('STATIC', 'route'), null);
        $this->params = ocConfig(array('STATIC', 'params'), array());
        $this->delimiter = ocConfig(array('STATIC', 'delimiter'), '-');
    }

    /**
     * 获取静态文件
     * @param string $module
     * @param string $controller
     * @param string $action
     * @param string $data
     * @return array|bool
     */
    public function getStaticFile($module, $controller, $action, $data = null)
    {
        if (empty($controller) || empty($action)) return false;

        $params = $this->params;

        if ($module) {
            if (!array_key_exists($module, $params)) {
                return false;
            }
            $params = $params[$module];
        }

        if (!array_key_exists($controller, $params)) {
            return false;
        }

        $params = $params[$controller];
        if (!array_key_exists($action, $params)) {
            return false;
        }

        $params = $params[$action];
        $mvcPathMap = $this->getMvcPathMap($module, $controller, $action);
        list($file, $param) = $this->getParamsPathMap($params, $module, $controller, $action, $data);
        $file = str_ireplace('{p}', $file, $mvcPathMap);

        return array($file, $param);
    }

    /**
     * 获取参数
     * @param integer $offset
     * @param array $params
     * @param array $data
     * @param string $paramsStr
     * @return array
     */
    private function getParams($offset, $params, $data, $paramsStr)
    {
        $paramData = array();

        foreach ($params as $key => $param) {
            if (preg_match('/^({([\w:]+)})$/i', $param, $mt)) {
                $param = explode(':', trim($mt[2], ':'));
                if (count($param) > 1) {
                    $name = $param[0];
                    $field = $param[1];
                } else {
                    $name = $field = $param[0];
                }
                if (is_array($data)) {
                    if (array_key_exists($field, $data) || array_key_exists($field = $name, $data)) {
                        $value = urlencode($data[$field]);
                    } else {
                        $value = false;
                    }
                } else {
                    $getKey = (integer)$key + $offset;
                    $value = ($value = ocService()->request->getGet($getKey)) ? urlencode($value) : false;
                }
                $paramsStr = trim(str_ireplace($mt[1], $value, $paramsStr), $this->delimiter);
                $paramData[$name] = $value;
            } else
                ocService()->error->show('fault_static_field');
        }

        return array($paramsStr, $paramData);
    }

    /**
     * 获取参数数据路径
     * @param array $params
     * @param string $module
     * @param string $controller
     * @param string $action
     * @param array $data
     * @return array
     */
    public function getParamsPathMap($params, $module, $controller, $action, $data)
    {
        $extensionName = '.' . $this->fileType;

        $offset = $module ? 3 : 2;
        $index = strrpos($params, OC_DIR_SEP);
        $pathStr = str_replace($this->delimiter, OC_DIR_SEP, substr($params, 0, $index));
        $fileStr = substr($params, $index ? $index + 1 : 0);

        if (!preg_match('/^{[\w:]+}(' . $this->delimiter . '{[\w:]+})*$/', $fileStr)) {
            ocService()->error->show('fault_static_field');
        }

        $pathParams = $pathStr ? explode(OC_DIR_SEP, trim($pathStr, OC_DIR_SEP)) : array();
        $fileParams = $fileStr ? explode($this->delimiter, trim($fileStr, $this->delimiter)) : array();

        list($pathStr, $paramData) = $this->getParams($offset, $pathParams, $data, $pathStr, true);
        list($fileStr, $paramData) = $this->getParams($offset + count($pathParams), $fileParams, $data, $fileStr);

        $path = ($fileStr ? $fileStr : $action) . $extensionName;
        return array($path, $paramData);
    }

    /**
     * 获取MVC路径
     * @param $module
     * @param $controller
     * @param $action
     * @return string
     */
    public function getMvcPathMap($module, $controller, $action)
    {
        if ($this->route && preg_match('/^{c}[\/-]{a}[\/-]{p}$/i', $this->route)) {
            $search = array('{c}', '{a}');
            $replace = array($controller, $action);
            $module = $module ? $module . OC_DIR_SEP : false;
            return $module . str_ireplace($search, $replace, $this->route);
        }

        ocService()->error->show('fault_static_route');
    }
}