<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   应用程序公共函数
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

defined('OC_PATH') or exit('Forbidden!');

use Ocara\Core\Ocara;
use Ocara\Core\Container;
use Ocara\Core\ServiceProvider;
use Ocara\Core\ExceptionHandler;
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
 * 获取数组元素值
 * @param mixed $key
 * @param array $data
 * @param null $default
 * @param bool $required
 * @return array|bool|mixed|null
 * @throws Exception
 */
function ocGet($key, array $data, $default = null, $required = false)
{
	if ($required) {
		if ($result = ocCheckKey(false, $key, $data, true, $default)) {
			return $result[0];
		}
		ocService()->error->show('not_exists_key', array($key));
	}
	
	return ocCheckKey(false, $key, $data, false, $default);
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
	return ocCheckKey(true, $key, $data);
}

/**
 * 检测键名
 * @param $onlyCheck
 * @param mixed $key
 * @param array $data
 * @param bool $arrayGet
 * @param null $default
 * @return array|bool|null
 */
function ocCheckKey($onlyCheck, $key, array $data, $arrayGet = false, $default = null)
{
	if (is_integer($key)) {
		if (array_key_exists($key, $data)) {
			return $onlyCheck ? true : ($arrayGet ? array($data[$key]) : $data[$key]);
		} else {
			return $onlyCheck ? false : $default;
		}
	}

	if (is_string($key)) {
		if (array_key_exists($key, $data)) {
			return $onlyCheck ? true : ($arrayGet ? array($data[$key]) : $data[$key]);
		}
		$key = trim($key, '.');
		if (false === strstr($key, '.') || $key === '') {
			return $onlyCheck ? false : $default;
		}
		$key = explode('.', $key);
	}
	
	if (is_array($key)) {
		foreach ($key as $value) {
			if (is_array($data) && array_key_exists($value, $data)) {
				$data = $data[$value];
			} else {
				return $onlyCheck ? false : $default;
			}
		}
		return $onlyCheck ? true : ($arrayGet ? array($data) : $data);
	}
	
	return $onlyCheck ? false : $default;
}

/**
 * 获取语言文本
 * @param string|array $name
 * @param array $params
 * @param null $default
 * @return array|null
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
	if ($result = ocContainer()->config->arrayGet($key)) {
		return $unEmpty && ocEmpty($result[0]) ? $default : $result[0];
	}

	if (func_num_args() >= 2) return $default;

	throw new Exception('No config for key ' . $key . '.');
}

/**
 * Ocara内部函数-解析数组组键
 * @param mixed $key
 * @return array
 */
function ocParseKey($key)
{
	if (is_integer($key)) {
		return array($key);
	}

	if (is_string($key)) {
		$key = trim($key, '.');
		return $key === OC_EMPTY ? array() : explode('.', $key);
	}
	
	return is_array($key) ? $key : array();
}

/**
 * 递归设置数组元素值
 * @param array $data
 * @param $key
 * @param $value
 * @return mixed
 * @throws Exception
 */
function ocSet(array &$data, $key, $value)
{
	$key = ocParseKey($key);
	$max = count($key) - 1;

	if ($max == 0) {
		return $data[$key[0]] = $value;
	}

	$pointer = &$data;

	for ($i = 0;$i <= $max;$i++) {
		if (!is_array($pointer)) {
			ocService()->error->show('need_array_to_set');
		}
		$k = $key[$i];
		if ($i == $max) {
			return $pointer[$k] = $value;
		} else {
			if (!array_key_exists($k, $pointer)) {
				$pointer[$k] = array();
			}
			$pointer = &$pointer[$k];
		}
	}
}

/**
 * 检查是否为非0的空值
 * @param string $content
 * @return bool
 */
function ocEmpty($content)
{
	return empty($content) && !($content === 0 || $content === '0');
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
 * @param $key
 * @return array|null
 */
function ocDel(array &$data, $key)
{
	$key = func_get_args();
	array_shift($key);
	if (empty($key)) return null;
	$result = array();

	foreach ($key as $val) {
		$ret = null;
		if ($val = ocParseKey($val)) {
			$max = count($val) - 1;
			$pointer = &$data;
			for ($i = 0; $i <= $max; $i++) {
				$k = $val[$i];
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
		}
		$result[] = $ret;
	}

	return count($key) == 1 && $result ? $result[0] : $result;
}

/**
 * 获取异常错误数据
 * @param $exception
 * @return array
 * @throws Exception
 */
function ocGetExceptionData($exception)
{
	if (!(is_object($exception) && $exception instanceof \Exception)) {
		ocService()->error->show('invalid_exception');
	}

	$errorType = 'exception_error';
	if ($exception instanceof ErrorException) {
		$errorType = 'program_error';
	}

	return array(
		'type'        	 => $errorType,
		'code'        	 => $exception->getCode(),
		'message'     	 => $exception->getMessage(),
		'file'        	 => $exception->getFile(),
		'line'        	 => $exception->getLine(),
		'trace'       	 => $exception->getTraceAsString(),
		'traceInfo'   	 => $exception->getTrace()
	);
}

/**
 * 获取默认服务提供器
 * @param $name
 * @param bool|string $getDefault
 * @return string
 */
function ocService($name = null, $getDefault = false)
{
    $services = ServiceProvider::getDefault();

    if (func_num_args()) {
        $object = null;
        if ($services) {
            $object = $services->getService($name);
        }
        if (empty($object) && $getDefault) {
            if (is_string($getDefault)) {
                $class = $getDefault;
            } else {
                if ($services) {
                    $class = $services->config->get('');
                } else {
                    $class = '\Ocara\Core\\' . ucfirst($name);
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
 * @return mixed\
 */
function ocContainer()
{
    return Container::getDefault();
}

/**
 * PHP中止执行时处理
 */
function ocShutdownHandler()
{
	$error = error_get_last();
	if ($error) {
		if (@ini_get('display_errors')) {
			ocService()->exceptionHandler->errorHandler($error['type'], $error['message'], $error['file'], $error['line']);
		}
	}
}

/**
 * 调试函数 - 使用var_dump打印输出并且停止代码执行
 * @param mixed $content
 */
function ocPrint($content)
{
	print_r($content);
	die();
}

/**
 * 调试函数 - 使用var_dump打印输出并且停止代码执行
 * @param mixed $content
 */
function ocDump($content)
{
	var_dump($content);
	die();
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
 * 获取提示内容
 * @param array $languages
 * @param $message
 * @param array $params
 * @return array
 */
function ocGetLanguage(array $languages, $message, array $params = array())
{
	$result = array('code' => 0, 'message' => $message);

	if (is_array($languages) && isset($languages[$message])) {
		$errorCode = 0;
		$content = ocGet($message, $languages);
		if (is_array($content)) {
			$errorCode = $content[0];
			$content   = $content[1];
		}
		$content = ocSprintf($content, $params);
		$content = str_ireplace('%s', OC_EMPTY, $content);
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
			if ($once) return include_once ($path);
			return include ($path);
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
 * 首字母小写-兼容PHP5.2版本框架，PHP5.3以上如果没有使用可删除
 * @param string $str
 * @return string
 */
function ocLf($str)
{
	return lcfirst($str);
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
		function($matchs)
		{
			return iconv('UCS-2BE', 'UTF-8', pack('H4', $matchs[1]));
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
 * @param inter $urlType
 * @param bool $static
 * @return string
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
			$filePath = ocCheckFilePath($filePath);
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
 * @param string $str
 * @param null $sep
 * @return mixed
 */
function ocHumpToLine($str, $sep = '_')
{
    $str = preg_replace_callback('/([A-Z]{1})/',function($matches) use ($sep) {
        return $sep . strtolower($matches[0]);
    }, $str);

    return $str;
}

/**
 * 检查文件路径
 * @param string $filePath
 * @return bool|mixed|string
 */
function ocCheckFilePath($filePath) 
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
	$result  = @chmod($path, $perm);

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
 * 获取完整路径
 * @param string $dir
 * @param string $path
 * @return bool|mixed|string
 */
function ocPath($dir, $path = null)
{
    return ocService('path', true)->get($dir, $path, OC_ROOT, true, false);
}

/**
 * 获取完整文件路径，检查文件是否存在
 * @param string $dir
 * @param string $path
 * @return bool|mixed|string
 */
function ocFile($dir, $path)
{
    return ocService('path', true)->get($dir, $path, OC_ROOT, true, true);
}

/**
 * 获取绝对URL
 * @param $dir
 * @param string $subPath
 * @param string $root
 * @return string
 */
function ocRealUrl($dir, $subPath = null, $root = false)
{
    $root = $root ? : OC_ROOT_URL;
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
 * 文件首字母大写
 * @param $path
 * @return string
 */
function ocUpperFile($path){
    return dirname($path) . OC_DIR_SEP . ucfirst(basename($path));
}

/**
 * 文件首字母小写
 * @param $path
 * @return string
 */
function ocLowerFile($path){
    return dirname($path) . OC_DIR_SEP . lcfirst(basename($path));
}