<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 应用程序公共函数
 * @Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

defined('OC_PATH') or exit('Forbidden!');

use Ocara\Core\Container;
use Ocara\Core\ServiceProvider;
use Ocara\Exceptions\Exception;
use Ocara\Exceptions\ErrorException;

/**
 * 统一路径，将反斜杠换成正斜杠
 * @param $path
 * @return mixed
 */
function ocCommPath($path)
{
    return str_replace("\\", OC_DIR_SEP, $path);
}

/**
 * 是否标量或null
 * @param mixed $data
 * @return bool
 */
function ocScalar($data)
{
    return is_scalar($data) || $data === null;
}

/**
 * 是否标量或null
 * @param mixed $data
 * @return bool
 */
function ocSimple($data)
{
    return is_string($data) || is_numeric($data);
}

/**
 * 检测键名是否存在
 * @param mixed $key
 * @param array $data
 * @return array|bool|mixed|null
 */
function ocKeyExists($key, array $data)
{
    return !!ocCheckKey($key, $data);
}

/**
 * 检测键名
 * @param mixed $key
 * @param array $data
 * @return array|bool|null
 */
function ocCheckKey($key, array $data)
{
    if (is_array($key)) {
        foreach ($key as $value) {
            if (is_array($data) && array_key_exists($value, $data)) {
                $data = $data[$value];
            } else {
                return false;
            }
        }
        return array($data);
    }

    if (is_string($key) || is_numeric($key)) {
        if (array_key_exists($key, $data)) {
            return array($data[$key]);
        }
    }

    return false;
}

/**
 * 获取语言文本
 * @param $name
 * @param array $params
 * @param mixed $default
 * @return null
 */
function ocLang($name, array $params = array(), $default = null)
{
    $result = ocService('lang', true)->get($name, $params);

    if ($result['message']) {
        return $result['message'];
    }

    $result = func_num_args() >= 3 ? $default : $name;
    return $result;
}

/**
 * 获取配置
 * @param $key
 * @param null $default
 * @param bool $unEmpty
 * @return null
 * @throws Exception
 */
function ocConfig($key, $default = null, $unEmpty = false)
{
    $argsLength = func_num_args();
    $hasDefault = $argsLength > 1;
    $key = is_array($key) ? $key : explode('.', $key);

    if ($result = ocContainer()->config->arrayGet($key)) {
        if (!$result[0]) {
            if ($unEmpty) {
                if (ocEmpty($result[0])) return $default;
            } else {
                if ($hasDefault) return $default;
            }
        }
        return $result[0];
    }

    if (!$hasDefault) {
        $key = implode('.', ocParseKey($key));
        throw new Exception('No config for key ' . $key . '.');
    }

    return $default;
}

/**
 * Ocara内部函数-解析数组组键
 * @param mixed $key
 * @return array
 */
function ocParseKey($key)
{
    return is_array($key) ? $key : array($key);
}

/**
 * 获取数组元素值
 * @param mixed $key
 * @param array $data
 * @param null $default
 * @param bool $required
 * @return array|bool|mixed|null
 */
function ocGet($key, array $data, $default = null, $required = false)
{
    if (is_string($key) || is_numeric($key)) {
        if (array_key_exists($key, $data)) {
            return $data[$key];
        }
    } elseif (is_array($key)) {
        $result = ocCheckKey($key, $data);
        if ($result) {
            return $result[0];
        }
    }

    if ($required) {
        ocService()->error->show('not_exists_key', array($key));
    }

    return $default;
}

/**
 * 递归设置数组元素值
 * @param array $data
 * @param $key
 * @param $value
 * @return mixed
 */
function ocSet(array &$data, $key, $value)
{
    if (!is_array($key)) {
        return $data[$key] = $value;
    }

    $max = count($key) - 1;
    $pointer = &$data;

    for ($i = 0; $i <= $max; $i++) {
        if (!is_array($pointer)) {
            ocService()->error->show('need_array_to_set');
        }
        $k = $key[$i];
        if ($i == $max) {
            return $pointer[$k] = $value;
        }
        if (!array_key_exists($k, $pointer)) {
            $pointer[$k] = array();
        }
        $pointer = &$pointer[$k];
    }
}

/**
 * 检查是否为非0的空值
 * @param string $content
 * @return bool
 */
function ocEmpty($content)
{
    return !$content && $content !== 0 && $content !== '0';
}

/**
 * 变量转换成指定键名的数组
 * @param mixed $content
 * @param bool $emptyStr
 * @return array
 */
function ocForceArray($content, $emptyStr = false)
{
    if (is_array($content)) {
        return $content;
    }

    if ($content || $emptyStr && !ocEmpty($content)) {
        return (array)$content;
    }

    return array();
}

/**
 * 递归array_map操作
 * @param mixed $callback
 * @param array $data
 * @return array
 */
function ocArrayMap($callback, array $data)
{
    foreach ($data as $key => $value) {
        if (is_array($data[$key])) {
            $data[$key] = ocArrayMap($callback, $data[$key]);
        } else {
            $data[$key] = call_user_func($callback, $data[$key]);
        }
    }
    return $data;
}

/**
 * 删除数组元素
 * @param array $data
 * @param mixed $key
 * @return array|null
 */
function ocDel(array &$data, $key)
{
    $result = array();

    if (func_num_args() >= 2) {
        $key = func_get_args();
        array_shift($key);
    }

    if (!$key) return null;

    foreach ($key as $value) {
        $ret = null;
        if ($value || $value === 0 || $value === '0') {
            if (is_array($value)) {
                $max = count($value) - 1;
                $pointer = &$data;
                for ($i = 0; $i <= $max; $i++) {
                    $k = $value[$i];
                    if (is_array($pointer) && array_key_exists($k, $pointer)) {
                        if ($i == $max) {
                            $ret = $pointer[$k];
                            $pointer[$k] = null;
                            unset($pointer[$k]);
                        } else {
                            $pointer = &$pointer[$k];
                        }
                    }
                }
            } elseif (is_string($value) || is_numeric($value)) {
                if (array_key_exists($value, $data)) {
                    $ret = $data[$value];
                    $data[$value] = null;
                    unset($data[$value]);
                }
            }
        }
        $result[] = $ret;
    }

    return count($key) == 1 && $result ? $result[0] : $result;
}

/**
 * 获取交集
 * @param $data
 * @param $keys
 * @return array
 */
function ocIntersectKey($data, $keys)
{
    $keysData = array_fill_keys($keys, null);
    if ($keysData) {
        $data = array_intersect_key($data, $keysData);
    }
    return $data;
}

/**
 * 获取异常错误数据
 * @param $exception
 * @return array
 */
function ocGetExceptionData($exception)
{
    $errorType = 'exception_error';
    if ($exception instanceof ErrorException) {
        $errorType = 'program_error';
    }

    return array(
        'type' => $errorType,
        'code' => $exception->getCode(),
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
        'traceInfo' => $exception->getTrace()
    );
}

/**
 * 获取默认服务提供器
 * @param string $name
 * @param bool $getDefault
 * @return Ocara\Core\ServiceProvider
 */
function ocService($name = null, $getDefault = false)
{
    $services = ServiceProvider::getDefault();

    if (func_num_args()) {
        $object = null;
        if ($services) {
            $object = $services->loadService($name);
        }
        if (empty($object) && $getDefault) {
            if (is_string($getDefault)) {
                $class = $getDefault;
            } else {
                $class = '\Ocara\Core\\' . ucfirst($name);
                if (!class_exists($class)) {
                    $class = '\Ocara\Service\\' . ucfirst($name);
                }
            }
            $object = new $class();
        }
        return $object;
    }

    return $services;
}

/**
 * 获取默认容器
 * @return Ocara\Core\Container
 */
function ocContainer()
{
    return Container::getDefault();
}

/**
 * PHP中止执行时处理
 */
function ocShutdownHandle()
{
    $error = error_get_last();
    if ($error) {
        if (@ini_get('display_errors') && !ocService()->response->isSent()) {
            ocService()
                ->exceptionHandler
                ->errorHandle($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }
}

/**
 * 输出错误或返回错误处理类
 * @param null $error
 * @param array $params
 * @return mixed|null
 */
function ocError($error = null, array $params = array())
{
    $error = ocService()->error;
    $args = func_get_args();

    if ($args) {
        return call_user_func_array(array(&$error, 'show'), $args);
    }

    return $error;
}

/**
 * 是否可回调
 * @param $callback
 * @param bool $requirePublic
 * @param bool $requireStatic
 * @return bool
 * @throws ReflectionException
 */
function ocIsCallable($callback, $requirePublic = true, $requireStatic = true)
{
    if (!is_callable($callback, true)) return false;

    if (is_string($callback)) {
        if (strstr($callback, '::')) {
            $callback = explode('::', $callback);
            $class = reset($callback);
            $method = isset($callback[1]) ? $callback[1] : null;
            if ($class && $method) {
                if (method_exists($class, $method)) {
                    $methodReflection = new ReflectionMethod($class, $method);
                    if ($requireStatic && !$methodReflection->isStatic()) return false;
                    return $requirePublic ? $methodReflection->isPublic() : true;
                }
            }
        } else {
            return function_exists($callback);
        }
    } elseif (is_array($callback)) {
        $object = reset($callback);
        $method = isset($callback[1]) ? $callback[1] : null;
        if ($object && $method) {
            if (method_exists($object, $method)) {
                $methodReflection = new ReflectionMethod($object, $method);
                return $requirePublic ? $methodReflection->isPublic() : true;
            }
        }
    }

    return false;
}

/**
 * 获取提示内容
 * @param array $languages
 * @param mixed $message
 * @param array $params
 * @return array
 */
function ocGetLanguage(array $languages, $message, array $params = array())
{
    $result = array('code' => 0, 'message' => $message);

    if (is_array($languages) && isset($languages[$message])) {
        $errorCode = 0;
        $content = $languages[$message] ?: null;
        if ($content) {
            if (is_array($content)) {
                $errorCode = $content[0];
                $content = $content[1];
            }
            $content = ocSprintf($content, $params);
            $content = str_ireplace('%s', OC_EMPTY, $content);
        }
        $result = array('code' => $errorCode, 'message' => (string)$content);
    }

    return $result;
}

/**
 * 替换参数
 * @param $content
 * @param $params
 * @return mixed|string
 */
function ocSprintf($content, $params)
{
    if ($params) {
        if (strstr($content, '%s')) {
            $content = vsprintf($content, $params);
        }
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $content = str_ireplace("{{$key}}", $value, $content);
            }
        }
    }

    return $content;
}

/**
 * 加载文件
 * @param string $path
 * @param bool $required
 * @param bool $once
 * @param array $vars
 * @return array|mixed
 * @throws Exception
 */
function ocImport($path, $required = true, $once = true, array $vars = array())
{
    if (is_string($path)) {
        if (ocFileExists($path)) {
            $vars && extract($vars);
            if ($once) return include_once($path);
            return include($path);
        } else {
            if ($required) {
                $files = explode(OC_DIR_SEP, trim($path, OC_DIR_SEP));
                ocService()->error->show('not_exists_file', array(end($files)));
            }
        }
    } elseif (is_array($path)) {
        $result = array();
        foreach ($path as $file) {
            $result[] = ocImport($file, $required, $once, $vars);
        }
        return $result;
    }
}

/**
 * 给目录结尾加上/号
 * @param string $path
 * @return string
 */
function ocDir($path)
{
    $args = is_array($path) ? $path : func_get_args();

    foreach ($args as $key => $dir) {
        $args[$key] = $dir ? rtrim($dir, OC_DIR_SEP) . OC_DIR_SEP : $dir;
    }

    return implode('', $args);
}

/**
 * 给命名空间加上\号
 * @param string $path
 * @return string
 */
function ocNamespace($path)
{
    $args = is_array($path) ? $path : func_get_args();

    foreach ($args as $key => $dir) {
        $args[$key] = $dir ? rtrim($dir, OC_NS_SEP) . OC_NS_SEP : $dir;
    }

    return implode('', $args);
}

/**
 * <br/>转nl
 * @param string $str
 * @return mixed
 */
function ocBr2nl($str)
{
    return preg_replace('/<br\\s*?\/??>/i', '', $str);
}

/**
 * JSON编码
 * @param $content
 * @return mixed|Services_JSON_Error|string
 */
function ocJsonEncode($content)
{
    if (defined('JSON_UNESCAPED_UNICODE')) {
        return json_encode($content, JSON_UNESCAPED_UNICODE);
    }

    $content = preg_replace_callback(
        '#\\\u([0-9a-f]{4})#i',
        function ($matches) {
            return iconv('UCS-2BE', 'UTF-8', pack('H4', $matches[1]));
        },
        json_encode($content)
    );

    return $content;
}

/**
 * 支持中文的basename
 * @param string $filePath
 * @return mixed
 */
function ocBasename($filePath)
{
    return preg_replace('/^.+[\\\\\\/]/', '', $filePath);
}

/**
 * 新建URL
 * @param $route
 * @param array $params
 * @param bool $relative
 * @param null $urlType
 * @param bool $static
 * @return mixed
 */
function ocUrl($route, $params = array(), $relative = false, $urlType = null, $static = true)
{
    return ocService()->url->create($route, $params, $relative, $urlType, $static);
}

/**
 * 文件是否存在(windows中区分大小写)
 * @param string $filePath
 * @param bool $check
 * @return bool|mixed|string
 */
function ocFileExists($filePath, $check = false)
{
    if ($filePath) {
        $filePath = ocCommPath($filePath);
        if ($check) {
            $filePath = ocCheckChineseFilePath($filePath);
        }
        if (is_file($filePath)) {
            if (OC_IS_WIN && ocBasename(ocCommPath(realpath($filePath))) != ocBasename($filePath)) {
                $exists = false;
            } else {
                $exists = true;
            }
            return $exists ? $filePath : false;
        }
    }

    return false;
}

/**
 * 下划线转驼峰式
 * @param string $name
 * @param string $sep
 * @return string
 */
function ocHump($name, $sep = OC_EMPTY)
{
    return implode($sep, array_map('ucfirst', explode('_', $name)));
}

/**
 * 驼峰式转下划线
 * @param $str
 * @param string $sep
 * @return null|string|string[]
 */
function ocHumpToLine($str, $sep = '_')
{
    $str = preg_replace_callback('/([A-Z]{1})/', function ($matches) use ($sep) {
        return $sep . strtolower($matches[0]);
    }, $str);

    return $str;
}

/**
 * 检查文件路径
 * @param string $filePath
 * @return bool|mixed|string
 */
function ocCheckChineseFilePath($filePath)
{
    if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', $filePath)) {
        $filePath = iconv('UTF-8', 'GBK', $filePath);
    }

    return $filePath;
}

/**
 * 设置文件或目录权限
 * @param string $path
 * @param integer $perm
 * @return bool
 */
function ocChmod($path, $perm)
{
    if (@chmod($path, $perm)) {
        return true;
    }

    $oldMask = umask(0);
    $result = @chmod($path, $perm);

    umask($oldMask);
    return $result;
}

/**
 * 计算程序运行时间
 */
function ocExecTime()
{
    $runtime = microtime(true) - OC_EXECUTE_START_TIME;
    return $runtime * 1000;
}

/**
 * 获取类的全名
 * @param $name
 * @return string
 */
function ocClassName($name)
{
    if (strstr($name, OC_NS_SEP)) {
        $name = OC_NS_SEP . ltrim($name, OC_NS_SEP);
    }

    return $name;
}

/*************************************************************************************************
 * 路径获取函数
 ************************************************************************************************/
/**
 * 获取协议主机
 * @param $host
 * @param bool $requireProtocol
 * @return string
 */
function ocHost($host, $requireProtocol = true)
{
    if ($requireProtocol) {
        return OC_PROTOCOL . '://' . ($host ? $host . OC_DIR_SEP: OC_EMPTY);
    }
    return ($host ? OC_PROTOCOL . '://' . $host : OC_EMPTY) . OC_DIR_SEP;
}

/**
 * 获取完整路径
 * @param $dir
 * @param null $path
 * @return mixed
 */
function ocPath($dir, $path = null)
{
    return ocService('path', true)->get($dir, $path, OC_APP_ROOT, true, false);
}

/**
 * 获取完整文件路径，检查文件是否存在
 * @param $dir
 * @param $path
 * @return mixed
 */
function ocFile($dir, $path)
{
    return ocService('path', true)->get($dir, $path, OC_APP_ROOT, true, true);
}

/**
 * 获取绝对URL
 * @param $dir
 * @param null $subPath
 * @param bool $root
 * @return mixed
 */
function ocRealUrl($dir, $subPath = null, $root = false)
{
    $root = $root ?: OC_ROOT_URL;
    return ocContainer()->path->get($dir, $subPath, $root, false, false);
}

/**
 * 获取相对URL
 * @param string $dir
 * @param string $subPath
 * @return bool|mixed|string
 */
function ocSimpleUrl($dir, $subPath)
{
    return ocContainer()->path->get($dir, $subPath, OC_DIR_SEP, false, false);
}

/**
 * 分隔目录
 * @param $filePath
 * @param $separateDir
 * @return mixed
 */
function ocSeparateDir($filePath, $separateDir)
{
    $rootPath = strstr($filePath, $separateDir, true) . ocDir($separateDir);
    $subDir = str_replace($rootPath, OC_EMPTY, $filePath) . OC_DIR_SEP;
    return array($rootPath, $subDir);
}

/**
 * 去除尾部字符串
 * @param $str
 * @param $tail
 * @return false|string
 */
function ocStripTail($str, $tail)
{
    $modelLength = strlen($tail);

    if (substr($str, -$modelLength) == $tail) {
        $str = substr($str, 0, -$modelLength);
    } else {
        $str = $str;
    }

    return $str;
}

/**
 * 文件首字母大写
 * @param $path
 * @return string
 */
function ocUpperFile($path)
{
    return dirname($path) . OC_DIR_SEP . ucfirst(basename($path));
}

/**
 * 文件首字母小写
 * @param $path
 * @return string
 */
function ocLowerFile($path)
{
    return dirname($path) . OC_DIR_SEP . lcfirst(basename($path));
}