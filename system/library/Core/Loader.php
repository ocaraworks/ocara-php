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
        $this->_defaultPath = OC_APPLICATION_PATH . 'library';

        $config = ocContainer()->config;
        $defaultAutoMap = $config->getDefault('NAMESPACE_MAP', array());
        $autoMap = $config->get('NAMESPACE_MAP', array());
        $result = array_merge($defaultAutoMap, $autoMap);

        $keys = $this->formatNamespaceKey(array_keys($result));
        $values = array_values($result);
        $this->_namespaceMap = array_combine($keys, $values);
        krsort($this->_namespaceMap);
    }

    /**
     * 注册命名空间
     * @param $namespace
     * @param $path
     */
    public function registerNamespace($namespace, $path)
    {
        $namespace = $this->formatNamespaceKey($namespace);
        $this->_namespaceMap[reset($namespace)] = $path;
        krsort($this->_namespaceMap);
    }

    /**
     * 格式化命名空间键名
     * @param $namespace
     * @return array
     */
    public function formatNamespaceKey($namespace)
    {
        $namespace = (array)$namespace;
        $replace = str_repeat(OC_NS_SEP, 2);

        foreach ($namespace as $key => $value) {
            if ($value{0} != OC_NS_SEP) {
                $value = OC_NS_SEP . $value;
            }
            $namespace[$key] = sprintf('/%s/', str_replace(OC_NS_SEP, $replace, $value));
        }

        return $namespace;
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
            $newClass = OC_NS_SEP . preg_replace('/[\\\\]+/', '\\', $newClass);
            $keys = array_keys($this->_namespaceMap);
            $values = array_values($this->_namespaceMap);
            $filePath = preg_replace($keys, $values, $newClass,1);
            if ($filePath == $newClass) {
                $filePath = $this->_defaultPath . $newClass;
            }
            $filePath .= '.php';
        }  else {
            $filePath = $this->_defaultPath . OC_DIR_SEP . $newClass . '.php';
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