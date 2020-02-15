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
     * @param $moduleType
     */
	public static function create($moduleType = 'common')
	{
		include (OC_SYS . 'resource/application/data.php');

		$cwd = dirname(dirname(ocCommPath(realpath($_SERVER['SCRIPT_FILENAME']))));
		self::$root  = str_replace('\\', OC_DIR_SEP, $cwd);
		self::$dirs  = $dirs;
		self::$files = $files;

		self::createDir();
		self::createFile($moduleType);
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
     * @param $moduleType
     */
	public static function createFile($moduleType)
	{
        $moduleType = lcfirst($moduleType);

		foreach (self::$files as $key => $value) {
		    if (is_array($value)) {
                foreach ($value as $v) {
                    $source = self::getFileSource($moduleType, $value, $key);
                    if (is_file($source)) {
                        $filePath = self::$root . "/{$key}/{$value}";
                        ocWrite($filePath, ocRead($source));
                    }
                }
            } else {
                $source = self::getFileSource($moduleType, $value, null, null);
                if (is_file($source)) {
                    $filePath = self::$root . "/{$value}";
                    ocWrite($filePath, ocRead($source, false));
                } else {
                    $source = self::getFileSource($moduleType, $value, null);
                    if (is_file($source)) {
                        $filePath = self::$root . "/{$key}/{$value}";
                        ocWrite($filePath, ocRead($source));
                    }
                }
            }
		}
	}

    /**
     * 获取文件来源
     * @param $moduleType
     * @param $key
     * @param $value
     * @param string $fileType
     * @return string
     */
	public static function getFileSource($moduleType, $value, $key, $fileType = 'ocara')
    {
        $fileType = $fileType ? '.' . $fileType : null;
        $source = OC_SYS . 'resource/application/files/';
        $templateFile = str_replace(OC_DIR_SEP, '.', ($key ? $key .'/' : null) . "{$value}" . $fileType);

        if (is_file($source . $moduleType . '/' . $templateFile)) {
            $source = $source . $moduleType . '/' . $templateFileNaked;
        } else {
            $source  = $source . $templateFile;
        }

        return $source;
    }

	/**
	 * 修改index.php内容
	 */
	public static function modifyIndex()
	{
		$file = $_SERVER["SCRIPT_FILENAME"];
		$content = ocRead($file);
		$content = preg_replace(
            "/Ocara[\\\\]Core[\\\\]Ocara::create\([\\\"\\\'\w]*\)/",
			'Ocara\\Core\\Ocara::run()',
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
