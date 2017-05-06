<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  应用生成类Application
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden');

//框架系统目录
define('OC_SYS', str_replace("\\", '/', realpath(OC_PATH) . '/system/'));

require_once (OC_SYS . 'functions/utility.php');
require_once (OC_SYS . 'functions/common.php');

final class Application
{
	public static $root;
	public static $dirs;
	public static $files;

	/**
	 * 应用生成
	 */
	public static function create()
	{
		include (OC_SYS . 'modules/application/data.php');

		$cwd = dirname(ocCommPath(realpath($_SERVER['SCRIPT_FILENAME'])));
		self::$root  = str_replace('\\', OC_DIR_SEP, $cwd);
		self::$dirs  = $dirs;
		self::$files = $files;

		self::createDir();
		self::createFile();
		self::modifyIndex();

		exit('Application create Success!');
	}

	/**
	 * 新建目录
	 */
	public static function createDir()
	{
		foreach (self::$dirs as $key => $value) {
			foreach ($value as $v) {
				$path = self::$root . OC_DIR_SEP . "{$key}/{$v}/";
				if (ocCheckPath($path)) {
					continue;
				} else {
					self::error($path);
				}
			}
		}
	}

	/**
	 * 新建文件
	 */
	public static function createFile()
	{
		foreach (self::$files as $key => $value) {

			foreach ($value as $v) {
				$filePath = self::$root . "/{$key}/{$v}";
				$source   = OC_SYS . 'modules/application/files/';
				$source  = $source . str_replace(OC_DIR_SEP, '.', "{$key}/{$v}");

				if (!ocFileExists($source)) {
					die("Lost ocara file : {$source}");
				}

				if (!$fo = @fopen($filePath, 'wb')) {
					self::error($filePath);
				}

				if (!@copy($source, $filePath)) {
					self::error($filePath);
				}
			}
		}
	}

	/**
	 * 修改index.php内容
	 */
	public static function modifyIndex()
	{
		$content  = "<?php\r\n";
		$content .= "\r\n";
		$content .= "//程序执行开始时间\r\n";
		$content .= "define('OC_EXECUTE_STATR_TIME', microtime(true));\r\n";
		$content .= "\r\n";
		$content .= "//框架所在目录,需配置正确\r\n";
		$content .= "define('OC_PATH', '" . OC_PATH . "');\r\n";
		$content .= "\r\n";
		$content .= "/*\r\n";
		$content .= " * 运行应用\r\n";
		$content .= " */\r\n";
		$content .= "require_once(OC_PATH . '/system/library/Ocara.php');\r\n";
		$content .= "Ocara\\Ocara::run();";

		self::write($_SERVER["SCRIPT_FILENAME"], $content);
	}

	/**
	 * 写入内容
	 * @param string $filePath
	 * @param string $content
	 */
	private static function write($filePath, $content)
	{
		if (!$fo = @fopen($filePath, 'wb')) {
			if (!is_writeable($filePath)) {
				if (!@chmod($filePath, 0777)) self::error($filePath);
			}
		}
		$result = fwrite($fo, $content);
		@fclose($fo);
	}

	/**
	 * 文件或目录不可写错误
	 * @param string $path
	 */
	private function error($path)
	{
		die("Please make sure the parent directory is writable : {$path}.");
	}
}
?>