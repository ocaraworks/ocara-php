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
	 */
	public static function init($rootPath)
	{
		self::$rootPath = $rootPath;
		self::_defineConst();
		require_once (OC_SYS . '/functions/utility.php');
		define('OC_ROOT',
			ocDir(rtrim(ocCommPath(realpath(self::$rootPath)), OC_DIR_SEP))
		);

		$cwdDir = self::_checkPath();
		chdir(OC_ROOT);
		define('OC_PHP_SAPI', 'cli');
		require_once (OC_SYS . 'const/basic.php');

		$dir = ocCommPath(dirname($_SERVER['SCRIPT_NAME']));
		$dir = trim(str_ireplace($cwdDir, OC_EMPTY, $dir), OC_DIR_SEP);
		define('OC_ROOT_URL',
			php_sapi_name() == 'cli' ? OC_DIR_SEP : OC_PROTOCOL  . '://' . ocDir(OC_HOST, $dir)
		);

		if (!is_file($path = OC_SYS . 'Ocara.php')) {
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

	/**
	 * 定义常量
	 */
	private static function _defineConst()
	{
		define('OC_EXECUTE_STA_TIME', microtime(true));
		define('OC_DIR_SEP', '/');
		define('OC_PHP_SELF', 'pass/' . basename($_SERVER['PHP_SELF']));
		define('OC_EMPTY', (string)false);
		define('OC_PATH', str_replace(DIRECTORY_SEPARATOR, OC_DIR_SEP, realpath(dirname(__FILE__) . '/../')));
		define('OC_SYS', str_replace(DIRECTORY_SEPARATOR, OC_DIR_SEP, realpath(OC_PATH) . '/system/'));
	}

	/**
	 * 获取目录信息
	 */
	private static function _checkPath()
	{
		$filename = ocCommPath(dirname(realpath($_SERVER['SCRIPT_FILENAME'])));
		$cwdDir   = str_ireplace(OC_ROOT, OC_EMPTY, $filename);
		$path     = explode(OC_DIR_SEP, trim($cwdDir, OC_DIR_SEP));

		if (in_array(reset($path), array('.', '..'))) {
			array_shift($path);
		}

		if (!($path && reset($path) == 'pass')) {
			die('Access denied!');
		}

		return $cwdDir;
	}
}
