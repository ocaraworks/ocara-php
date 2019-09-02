<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  应用生成类ApplicationGenerator
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

defined('OC_PATH') or exit('Forbidden');

final class ApplicationGenerator
{
	public static $root;
	public static $dirs;
	public static $files;

	/**
	 * 应用生成
	 */
	public static function create()
	{
		include (OC_SYS . 'resource/application/data.php');

		$cwd = dirname(dirname(ocCommPath(realpath($_SERVER['SCRIPT_FILENAME']))));
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
					self::error($path, 'writable');
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
				$filePath = self::$root . "/{$key}/{$v}.php";
				$source   = OC_SYS . 'resource/application/files/';
				$source  = $source . str_replace(OC_DIR_SEP, '.', "{$key}/{$v}.ocara");
				ocWrite($filePath, ocRead($source));
			}
		}
	}

	/**
	 * 修改index.php内容
	 */
	public static function modifyIndex()
	{
		$file = $_SERVER["SCRIPT_FILENAME"];
		$content = ocRead($file);
		$content = str_ireplace(
			'Ocara\\Ocara:create()',
			'Ocara\\Ocara:run()',
			$content
		);

		ocWrite($file, $content);
	}

    /**
     * 文件或目录不可写错误
     * @param $path
     * @param $type
     */
	private static function error($path, $type)
	{
		die("Please make sure the parent directory is {$type} : {$path}.");
	}
}
