<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 路径生成类Path
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

final class Path extends Base
{
	/**
	 * 单例模式
	 */
	private static $_instance = null;

	/**
	 * 路径信息
	 */
	private static $_data;
	
	private function __clone(){}
	private function __construct(){}

	public static function getInstance()
	{
		if (self::$_instance === null) {
			self::$_instance = new self();
			self::initialize();
		}
		return self::$_instance;
	}

	/**
	 * 初始化
	 */
	public static function initialize()
	{
		self::$_data = ocConfig('APP_PATH_INFO');
		self::$_data['map']['lang'] = 'lang/' . Ocara::language();
	}
	
	/**
	 * 生成文件或目录的路径
	 * @param string  $dir
	 * @param string  $path
	 * @param string  $root
	 * @param bool $local
	 * @param bool $isFile
	 */
	public static function get($dir, $path, $root = false, $local = true, $isFile = true)
	{
		Path::getInstance();
		$mapDir = $dir;
		
		if (isset(self::$_data['map'][$dir])) {
			$mapDir = self::$_data['map'][$dir];
		}

		$mapDir = strtr($dir, self::$_data['belong']) . OC_DIR_SEP . $mapDir;
		$result = ocDir($root, $mapDir) . $path;
		if (isset($result)) {
			if ($local && $isFile && ($result = ocFileExists($result)) == false) {
				Error::show('not_exists_file', array($path));
			}
			$path = $result;
		}

		return $path;
	}
}