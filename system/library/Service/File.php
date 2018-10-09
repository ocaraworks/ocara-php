<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架    文件和目录处理插件File
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Service;

use Ocara\Core\ServiceBase;

class File extends ServiceBase
{
	/**
	 * 新建空文件
	 * @param string $filePath
	 * @param integer $perm
	 * @param null $mode
	 * @return bool|mixed|string
	 */
	public static function createFile($filePath, $perm = null, $mode = null)
	{
		if (ocFileExists($filePath, true)) return $filePath;

		ocCheckPath(dirname($filePath), $mode);
		$filePath = ocCheckFilePath($filePath);
		
		if (function_exists('file_put_contents')) {
			$result = @file_put_contents($filePath, OC_EMPTY);
			return $result >= 0 ? $filePath : false;
		}
		
		if (!($fo = @fopen($filePath, 'wb'))) return false;
		
		if ($perm && !chmod($filePath, $perm)) {
			return false;
		}
		
		@fclose($fo);
		
		return $filePath;
	}

	/**
	 * @param string $filePath
	 * @param string $openMode
	 * @param integer $perm
	 * @param bool $createDir
	 * @return bool|resource
	 */
	public static function openFile($filePath, $openMode, $perm = null, $createDir = true)
	{
		if (!ocFileExists($filePath, true)) {
			if (!$createDir || !self::createFile($filePath, $perm)) {
				return false;
			} 
		} 
		
		return @fopen($filePath, $openMode);
	}

	/**
	 * 关闭文件
	 * @param source $source
	 * @return bool
	 */
	public static function closeFile($source)
	{
		if (is_resource($source)) {
			return @fclose($source);
		}
		
		return false;
	}

	/**
	 * 重命名文件
	 * @param string $file
	 * @param string $newName
	 * @return bool
	 */
	public static function rename($file, $newName)
	{
		if ($file = ocFileExists($file, true)) {
			$newFile = ocCheckFilePath(dirname($file) . OC_DIR_SEP. $newName);	
			return ocFileExists($newFile) ? false : @rename($file, $newFile);
		}
		
		return false;
	}

	/**
	 * 删除文件
	 * @param string $filePath
	 * @return bool
	 */
	public static function delFile($filePath)
	{
		if ($filePath = ocFileExists($filePath, true)) {
			return is_writable($filePath) ? @unlink($filePath) : false;
		}
		
		return true;
	}

	/**
	 * 读文件的所有内容
	 * @param string $filePath
	 * @return bool|\mix|string
	 */
	public static function readFile($filePath)
	{
		return ocRead($filePath);
	}

	/**
	 * 向文件写入内容
	 * @param string $path
	 * @param string $content
	 * @param integer $perm
	 * @param bool $trim
	 * @return bool|int|void
	 */
	public static function writeFile($path, $content, $perm = null, $trim = false)
	{
		return ocWrite($path, self::_getContent($content, $trim), false, $perm);
	}

	/**
	向文件写入一行
	 * @param string $path
	 * @param string $content
	 * @param integer $perm
	 * @param bool $trim
	 * @return bool|int|void
	 */
	public static function appendFile($path, $content, $perm = null, $trim = false)
	{
		return ocWrite($path, self::_getContent($content, $trim), true, $perm);
	}

	/**
	 * 复制文件
	 * @param string $source
	 * @param string $destination
	 * @return bool
	 */
	public static function copyFile($source, $destination)
	{
		$source = ocFileExists($source, true);
		$destination = ocCheckFilePath($destination, true);

		if ($source) {
			$path = dirname($destination);
			if (ocCheckPath($path)) {
				return @copy($source, $destination);
			}
		}
		
		return false;
	}

	/**
	 * 移动文件
	 * @param string $source
	 * @param string $destination
	 * @return bool
	 */
	public static function moveFile($source, $destination)
	{
		$source = ocFileExists($source, true);
		$destination = ocCheckFilePath($destination, true);
		
		if ($source) {
			$path = dirname($destination);
			if (ocCheckPath($path)) {
				return @rename($source, $destination);
			}
		}
		
		return false;
	}

	/**
	 * 获取文件信息
	 * @param string $filePath
	 * @return array
	 */
	public static function fileInfo($filePath)
	{
		if (!ocFileExists($filePath)) return array();
		
		date_default_timezone_set('PRC');
		
		return array(
			'atime' => @date('Y-m-d h:i:s', fileatime($filePath)), 
			'ctime' => @date('Y-m-d h:i:s', filectime($filePath)), 
			'mtime' => @date('Y-m-d h:i:s', filemtime($filePath)), 
			'perms' => @substr(sprintf("%o", fileperms($filePath)), -4), 
			'size' 	=> @filesize($filePath), 
			'type' 	=> @filetype($filePath)
		);
	}

	/**
	 * 检查并新建目录
	 * @param string $path
	 * @param integer $perm
	 * @return bool
	 */
	public static function createDir($path, $perm = null)
	{
		return ocCheckPath($path, $perm);
	}

	/**
	 * 删除目录，支持递归删除
	 * @param string $path
	 * @param bool $recursive
	 * @return bool
	 */
	public static function delDir($path, $recursive = false)
	{
		return self::_delDir($path, $recursive, 'del');
	}

	/**
	 * 清空目录，支持递归
	 * @param string $path
	 * @param bool $recursive
	 * @return bool
	 */
	public static function clearDir($path, $recursive = false)
	{
		return self::_delDir($path, $recursive, 'clear');
	}

	/**
	 * 类内部函数,删除或清空目录
	 * @param string $path
	 * @param bool $recursive
	 * @param string $delType
	 * @return bool
	 */
	private static function _delDir($path, $recursive = false, $delType = 'del')
	{
		if (!$path) return false;
		if (!is_dir($path)) return true;

		$subElements = scandir($path);
		ocDel($subElements, 0, 1);
	
		if ($subElements && ($delType == 'del' && $recursive || $delType == 'clear')) {
			foreach ($subElements as $key => $element) {
				ocDel($subElements, $key);
				$subPath = $path . OC_DIR_SEP . $element;
				if (is_file($subPath)) {
					if (!self::delFile($subPath)) return false;
				} elseif (is_dir($subPath)) {
					if (!$recursive) continue;
					if (!self::delDir($subPath, true, 'del')) return false;
				}
			}
		}
		
		if ($delType == 'del') {
			return $subElements ? false : @rmdir($path);
		}
		
		return true;
	}

	/**
	 * 获取内容
	 * @param string|array $content
	 * @param bool $trim
	 * @return mixed
	 */
	private static function _getContent($content, $trim)
	{
		if ($trim) {
			if (is_array($content)) {
				foreach ($content as $key => $row) {
					$content[$key] = trim($row, $trim);
				}
			} else {
				$content = trim($content, $trim);
			}
		}

		return ocService('filter', true)->bom($content);
	}
}
