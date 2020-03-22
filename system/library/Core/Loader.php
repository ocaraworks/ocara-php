<?php
/**
 * Ocara开源框架 自动加截处理类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use \ReflectionClass;
use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class Loader extends Basis
{
    private $defaultPath;
    private $namespaceMap;

    public function __construct()
    {
        $this->defaultPath = ocCommPath(OC_APP_ROOT . 'support');

        $config = ocContainer()->config;
        $defaultAutoMap = $config->getDefault('NAMESPACE_MAP', array());
        $autoMap = $config->get('NAMESPACE_MAP', array());
        $result = array_merge($defaultAutoMap, $autoMap);

        $keys = $this->formatNamespaceKey(array_keys($result));
        $values = array_values($result);
        $this->namespaceMap = array_combine($keys, $values);

        krsort($this->namespaceMap);
    }

    /**
     * 注册命名空间
     * @param $namespace
     * @param $path
     */
    public function registerNamespace($namespace, $path)
    {
        $namespace = $this->formatNamespaceKey($namespace);
        $this->namespaceMap[reset($namespace)] = $path;
        krsort($this->namespaceMap);
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
     * @throws Exception
     */
    public function autoload($class)
    {
        $newClass = trim($class, OC_NS_SEP);

        if (strstr($newClass, OC_NS_SEP)) {
            $newClass = OC_NS_SEP . preg_replace('/[\\\\]+/', '\\', $newClass);
            $keys = array_keys($this->namespaceMap);
            $values = array_values($this->namespaceMap);
            $filePath = preg_replace($keys, $values, $newClass, 1);
            if ($filePath == $newClass) {
                $filePath = $this->defaultPath . $newClass;
            }
        } else {
            $filePath = $this->defaultPath . OC_DIR_SEP . $newClass;
        }

        $filePath = ocCommPath($filePath . '.php');

        if (ocFileExists($filePath)) {
            include_once($filePath);
            if (class_exists($newClass, false) || interface_exists($newClass, false)) {
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
                    try {
                        $reflection = new ReflectionClass($className);
                        $className = $reflection->getName();
                    } catch (\Exception $exception) {
                        throw new Exception($exception->getMessage(), $exception->getCode());
                    }
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
    }
}