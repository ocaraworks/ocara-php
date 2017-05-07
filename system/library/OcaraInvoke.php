<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   pass目录中框架调用函数
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

final class OcaraInvoke
{
	public static $rootPath;

	/**
	 * 初始化
	 * @param string $rootPath
	 * @param string $fileSelf
	 */
	public static function init($rootPath, $fileSelf)
	{
		define('OC_EXECUTE_START_TIME', microtime(true));
		define('OC_ROOT', self::getCommPath(realpath($rootPath)) . '/');
		define('OC_PATH', self::getCommPath(realpath(dirname(dirname(__DIR__)))) . '/');
		define('OC_PHP_SAPI', 'cli');

		define('OC_PHP_SELF',
			ltrim(str_replace(OC_ROOT, '', self::getCommPath(realpath($fileSelf))), '/')
		);

//		require_once (OC_SYS . '/functions/utility.php');
//
//		$cwdDir = self::_checkPath();
//		chdir(OC_ROOT);
//
//		require_once (OC_SYS . 'const/basic.php');
//
//		$dir = ocCommPath(dirname($_SERVER['SCRIPT_NAME']));
//		$dir = trim(str_ireplace($cwdDir, '', $dir), '/');
//		define('OC_ROOT_URL',
//			php_sapi_name() == 'cli' ? '/' : OC_PROTOCOL  . '://' . ocDir(OC_HOST, $dir)
//		);

		if (!is_file($path = OC_PATH . '/system/library/Ocara.php')) {
			die('Lost ocara file!');
		}

		include_once($path);
		if (!class_exists('\Ocara\Ocara', false)) {
			die('Lost Ocara class!');
		}

		Ocara::getInstance();
	}

	/**
	 * 运行框架
	 * @param string $route
	 * @param array $params
	 */
	public static function run($route, array $params = array())
	{
		$_GET = array_merge(array_values(Ocara::parseRoute($route)), $_GET);

		if (!ocFileExists(OC_ROOT . '.htaccess')) {
			Ocara::createHtaccess($moreContent = false);
		}

		$_GET = Ocara::formatGet(array_merge($_GET, $params));
		Ocara::boot();
	}

	private static function getCommPath($path)
	{
		return str_replace(DIRECTORY_SEPARATOR, '/', $path);
	}

	/**
	 * 获取目录信息
	 */
	private static function _checkPath()
	{
		$filename = ocCommPath(dirname(realpath($_SERVER['SCRIPT_FILENAME'])));
		$cwdDir   = str_ireplace(OC_ROOT, '', $filename);
		$path     = explode('/', trim($cwdDir, '/'));

		if (in_array(reset($path), array('.', '..'))) {
			array_shift($path);
		}

		if (!($path && reset($path) == 'pass')) {
			die('Access denied!');
		}

		return $cwdDir;
	}
}
