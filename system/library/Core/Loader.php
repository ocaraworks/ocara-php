<?php
/**
 * 自动加截处理类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use \ReflectionClass;
use Ocara\Exceptions\Exception;

class Loader extends Basis
{
    private $defaultPath;
    private $namespaceMaps = array();

    /**
     * Loader constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->defaultPath = ocCommPath(OC_APP_ROOT . 'support');
        $namespaceMps = ocContainer()->config->getDefault('NAMESPACE_MAP', array());
        $this->registerNamespace($namespaceMps);
    }

    /**
     * 注册命名空间
     * @param string $namespace
     * @param string $path
     */
    public function registerNamespace($namespace, $path = null)
    {
        if (!is_array($namespace)) {
            $namespace = array($namespace => $path);
        }

        $namespace = $this->formatNamespaceKey($namespace);
        $this->namespaceMaps = array_merge($this->namespaceMaps, $namespace);

        krsort($this->namespaceMaps);
    }

    /**
     * 获取命名空间
     * @return array
     */
    public function getNamespaceMaps()
    {
        return $this->namespaceMaps;
    }

    /**
     * 格式化命名空间键名
     * @param string $namespace
     * @return array
     */
    public function formatNamespaceKey($namespace)
    {
        $result = array();
        $namespace = (array)$namespace;
        $replace = str_repeat(OC_NS_SEP, 2);

        foreach ($namespace as $key => $value) {
            if (substr($key, -1) != OC_NS_SEP) {
                $key .= OC_NS_SEP;
            }
            if ($key{0} != OC_NS_SEP) {
                $key = OC_NS_SEP . $key;
            }
            $key = sprintf('/%s/', str_replace(OC_NS_SEP, $replace, $key));
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * 自动加载类
     * @param string $class
     * @return bool
     * @throws Exception
     */
    public function autoload($class)
    {
        $newClass = trim($class, OC_NS_SEP);

        if (strstr($newClass, OC_NS_SEP)) {
            $newClass = OC_NS_SEP . preg_replace('/[\\\\]+/', '\\', $newClass);
            $keys = array_keys($this->namespaceMaps);
            $values = array_values($this->namespaceMaps);
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