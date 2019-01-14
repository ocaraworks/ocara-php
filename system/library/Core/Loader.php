<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   AJAX请求处理类Ajax
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use \ReflectionClass;
use Ocara\Core\Basis;
use Ocara\Core\Container;

defined('OC_PATH') or exit('Forbidden!');

class Loader extends Basis
{
    private $_defaultPath;
    private $_namespaceMap;

    public function __construct()
    {
        $this->_defaultPath = OC_ROOT . 'library/';

        $config = ocContainer()->config;
        $defaultAutoMap = $config->getDefault('NAMESPACE_MAP', array());
        $autoMap = $config->get('NAMESPACE_MAP', array());

        $this->_namespaceMap = array_merge($defaultAutoMap, $autoMap);
    }

    /**
     * 注册命名空间
     * @param $namespace
     * @param $path
     */
    public function registerNamespace($namespace, $path) {
        $this->_namespaceMap[$namespace] = $path;
    }

    /**
     * 自动加载类
     * @param $class
     * @return bool
     * @throws \ReflectionException
     */
    public function autoload($class)
    {
        $newClass = trim($class, OC_NS_SEP);

        if (strstr($newClass, OC_NS_SEP)) {
            $filePath = strtr($newClass, $this->_namespaceMap);
            if ($filePath == $newClass) {
                $filePath = $this->_defaultPath . $newClass;
            }
            $filePath .= '.php';
        }  else {
            $filePath = $this->_defaultPath . $newClass . '.php';
        }

        $filePath = ocCommPath($filePath);

        if (ocFileExists($filePath)) {
            include_once($filePath);
            if (class_exists($newClass, false)
                || interface_exists($newClass, false)
            ) {
                return true;
            }
        }

        $autoLoads = spl_autoload_functions();
        foreach ($autoLoads as $func) {
            if (is_string($func)) {
                call_user_func_array($func, array($class));
            } elseif (is_array($func)) {
                $className = reset($func);
                if (is_object($className)) {
                    $reflection = new ReflectionClass($className);
                    $className = $reflection->getName();
                }
                if ($className === __CLASS__) continue;
                call_user_func_array($func, array($class));
            } else {
                continue;
            }
            if (class_exists($class, false) || interface_exists($newClass, false)) {
                return true;
            }
        }

        ocService('error', true)->show('not_exists_class', array($class));
    }
}