<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   公用函数
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
use Ocara\Error;
use Ocara\Lang;
use Ocara\Path;
use Ocara\GlobalVar;
use Ocara\Request;

defined('OC_PATH') or exit('Forbidden!');

/**
 * 替换空白字符
 * @param string $string
 * @param string $replace
 */
function ocReplaceSpace($string, $replace = '')
{
	return preg_replace('/[\s\v]+/', $replace, $string);
}

/**
 * common-检查扩展
 * @param string $extension
 * @param bool $required
 */
function ocCheckExtension($extension, $required = true)
{
	if (!extension_loaded($extension)) {
		if ($required) {
			Error::show('failed_load_extension', array($extension));
		}
		return false;
	}
	
	return true;
}

/**
 * 设置或获取全局变量
 * @param string $variable
 * @param mixed $value
 */
function ocGlobal($name, $value = null)
{
	if (func_num_args()>=2) {
		GlobalVar::set($name, $value);
	} else {
		return GlobalVar::get($name);
	}
}

/**
 * 对象递归转换成关联数组
 * @param mixed $data
 */
function ocArray($data)
{
	if (is_object($data)) {
		$data = get_object_vars($data);
	}
	
	if (is_array($data)) {
		return array_map(__FUNCTION__, $data);
	} else {
		return $data;
	}
}

/**
 * 检测是否关联数组
 * @param $data
 */
function ocAssoc(array $data)
{
	return array_keys($data) !== range(0, count($data) - 1);
}

/**
 * 数组递归转换成对象
 * @param mixed $data
 */
function ocObject($data)
{
	if (is_array($data)) {
		return (object)array_map(__FUNCTION__, $data);
	} else {
		return $data;
	}
}

/**
 * 检查路径是否存在，如果不存在则新建
 * @param string $path
 * @param integer $mode
 * @param bool $required
 */
function ocCheckPath($path, $mode = null, $required = false)
{
	if (empty($path)) return false;

	if (!is_dir($path)) {
		if (!$mode) $mode = 0755;
		if (!@mkdir($path, $mode, true)) {
			if ($required) {
				Error::show('failed_make_dir');
			} else {
				return false;
			}
		}
	}

	return is_dir($path);
}

/**
 * 新建类实例
 * @param string $name
 * @param array $params
 */
function ocClass($name, array $params = array())
{
	if ($params) {
		$refelction = new ReflectionClass($name);
		$object = $refelction->newInstanceArgs($params);
	} else {
		$object = new $name();
	}
	
	return $object;
}

/**
 * 加载函数库文件
 * @param string $filePath
 */
function ocFunc($filePath)
{
	$filePath = ocCommPath($filePath);
	$filePath = $filePath . '.php';

	if (ocFileExists($file = ocPath('functions', $filePath))OR 
		ocFileExists($file = OC_SYS . '/service/functions/' . $filePath) OR 
		ocFileExists($file = OC_SYS . '../extension/service/functions/' . $filePath)
	) {
		ocImport($filePath);
	}

	Error::show('not_exists_function_file');
}

/**
 * 使用原生的SQL语句，防止框架进行SQL安全过滤和转义
 * @param string $sql
 */
function ocSql($sql)
{
	if (is_string($sql) || is_numeric($sql)) {
		$sql = Request::stripOcaraTag($sql);
		return OC_SQL_TAG . $sql;
	}
	
	return $sql;
}

/**
 * 是否是标准名称
 * @param $field
 * @return int
 */
function ocIsStandardName($name)
{
	return preg_match('/^[^\d]\w*$/', $name);
}

/**
 * 获取语言
 * @param string|array $key
 * @param mixed $params
 */
function ocLang($name, array $params = array(), $default = null)
{
	$result = Lang::get($name, $params);

	if ($result['message']) {
		return $result['message'];
	}
	
	$result = func_num_args() >= 3 ? $default : $name;
	return $result;
}


/*************************************************************************************************
 * 路径获取函数
 ************************************************************************************************/

/**
 * 获取完整路径
 * @param string $dir
 * @param string $path
 */
function ocPath($dir, $path = false)
{
	return Path::get($dir, $path, OC_ROOT, true, false);
}

/**
 * 获取完整文件路径，检查文件是否存在
 * @param string $dir
 * @param string $path
 */
function ocFile($dir, $path)
{
	return Path::get($dir, $path, OC_ROOT, true, true);
}

/**
 * 获取绝对URL
 * @param string $dir
 * @param string $file
 * @param string $root
 */
function ocRealUrl($dir, $subPath = false, $root = false)
{
	$root = $root ? $root : OC_ROOT_URL;

	return Path::get($dir, $subPath, $root, false, false);
}

/**
 * 获取相对URL
 * @param string $dir
 * @param string $file
 */
function ocSimpleUrl($dir, $subPath)
{
	return Path::get($dir, $subPath, OC_DIR_SEP, false, false);
}

/*************************************************************************************************
 * 内容处理函数
 ************************************************************************************************/

/**
 * 写入文件
 * @param string $filePath
 * @param string|array $content
 * @param bool $append
 * @param integer $perm
 */
function ocWrite($filePath, $content, $append = false, $perm = 0755)
{
	if (is_dir($filePath)) {
		Error::show('exists_dir');
	}

	$dirPath  = dirname($filePath);
	$filePath = ocCheckFilePath($dirPath
				. OC_DIR_SEP
				. ocBasename($filePath));
	$result   = false;

	if (ocCheckPath($dirPath, $perm)) {

		if (!is_writable($dirPath)) {
			Error::show('no_dir_write_perm');
		}

		if($fo = @fopen($filePath, $append ? 'ab' : 'wb')) {
			if (false === flock($fo, LOCK_EX | LOCK_NB)) {
				Error::show('failed_file_lock');
			}
			if (is_array($content)) {
				foreach ($content as $row){
					$result = @fwrite($fo, $row . PHP_EOL);
					if (!$result) break;
				}
			} else {
				$result = @fwrite($fo, $content);
			}
			flock($fo, LOCK_UN);
			@fclose($fo);
		} else {
			if (ocFileExists($filePath)) {
				if (function_exists('file_put_contents')) {
					$writeMode = $append ? FILE_APPEND : LOCK_EX;
					if (is_array($content)) {
						$content = implode(PHP_EOL, $content);
					}
					$result = @file_put_contents($filePath, $content, $writeMode);
				}
			}
		}
	}
	
	return $result;
}

/**
 * 获取本地文件内容
 * @param string $filePath
 * @param bool $checkPath
 */
function ocRead($filePath, $checkPath = true)
{
	if ($checkPath && !preg_match('/^(.+)?\.\w+$/', $filePath)) {
		Error::show('invalid_path', array($filePath));
	}

	$filePath = ocCheckFilePath(dirname($filePath)
				. OC_DIR_SEP
				. ocBasename($filePath));
	$content = OC_EMPTY;

	if (ocFileExists($filePath)) {
		if (!is_readable($filePath)) {
			Error::show('no_file_read_perm');
		}
		if ($fo = @fopen($filePath, 'rb')) {
			if (false === flock($fo, LOCK_SH | LOCK_NB)) {
				Error::show('failed_file_lock');
			}
			@fseek($fo, 0);
			while (!@feof($fo)) {
				$content = $content . @fgets($fo);
			}
			flock($fo, LOCK_UN);
			@fclose($fo);
		} else {
			if (function_exists('file_get_contents')) {
				$content = @file_get_contents($filePath);
			}
		}
	}
	
	return $content;
}

/**
 * 获取远程内容
 * @param string $url
 * @param mixed $data
 * @param array $headers
 */
function ocRemote($url, $data = null, array $headers = array())
{
	if (@ini_get('allow_url_fopen'))
	 {
		if (function_exists('file_get_contents')) {
			if (empty($data)) {
				return @file_get_contents($url);
			}

			$data   = http_build_query($data, OC_EMPTY, '&');
			$header = "Content-type: application/x-www-form-urlencoded\r\n";
			$header = $header . "Content-length:" . strlen($data) . "\r\n";
			
			if ($headers) {
				foreach ($headers as $value) {
					$header = $header . $value . "\r\n";
				}
			}
			
			$context['http'] = array(
				'method'  => 'POST',
				'header'  => $header,
				'content' => $data
			);
			
			return @file_get_contents($url, false, stream_context_create($context));
		}
		return ocCurl($url, $data, $headers);
	}
	
	return null;
}

/**
 * 使用CURL扩展获取远程内容
 * @param string $url
 * @param string $data
 * @param array $headers
 * @param bool $showError
 */
function ocCurl($url, $data = null, array $headers = array(), $showError = false)
{
	if (function_exists('curl_init')) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		if ($headers) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		
		if ($data) {
			curl_setopt($ch, CURLOPT_POST, 1);
			if (is_array($data)) {
				$data = http_build_query($data);
			}
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
		
		curl_setopt($ch, CURLOPT_FAILONERROR, 1);
		$content = curl_exec($ch);
	
		if ($showError && $error = curl_error($ch)) {
			curl_close($ch);
			Error::show('failed_curl_return', array(curl_error($ch)));
		}
		
		curl_close($ch);

		return $content;
	}
	
	return null;
}

/**
 * 获取二维数组字段值（保留原来的KEY）
 * @param array $array
 * @param string $field
 * @return array
 */
function ocColumn(array $array, $field)
{
	$data = array();
	foreach ($array as $key => $value) {
		$data[$key] = $value[$field];
	}
	return $data;
}

/**
 * 兼容函数
 */
if (!function_exists('array_column')) {
	function array_column($array, $field) {
		$data = array();
		foreach ($array as $key => $value) {
			$data[] = $value[$field];
		}
		return $data;
	}
}