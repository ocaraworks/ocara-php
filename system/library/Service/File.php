<?php
/**
 * 文件和目录处理插件类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Service;

use Ocara\Exceptions\Exception;
use Ocara\Core\ServiceBase;

class File extends ServiceBase
{
    /**
     * 新建空文件
     * @param string $filePath
     * @param int $perm
     * @param string $openMode
     * @return bool
     * @throws Exception
     */
    public function createFile($filePath, $perm = null, $openMode = null)
    {
        $openMode = $openMode ?: 'wb';

        if (ocFileExists($filePath)) return $filePath;

        ocCheckPath(dirname($filePath), $perm);

        if (function_exists('file_put_contents')) {
            $result = @file_put_contents($filePath, OC_EMPTY);
            return $result >= 0 ? $filePath : false;
        }

        if (!($fo = @fopen($filePath, $openMode))) return false;

        if ($perm && !chmod($filePath, $perm)) {
            return false;
        }

        @fclose($fo);

        return $filePath;
    }

    /**
     * 打开文件
     * @param string $filePath
     * @param int $perm
     * @param string $openMode
     * @param bool $createDir
     * @return bool|false|resource
     * @throws Exception
     */
    public function openFile($filePath, $openMode, $perm = null, $createDir = true)
    {
        if (!ocFileExists($filePath)) {
            if (!$createDir || !$this->createFile($filePath, $perm)) {
                return false;
            }
        }

        return @fopen($filePath, $openMode);
    }

    /**
     * 关闭文件
     * @param resource $source
     * @return bool
     */
    public function closeFile($source)
    {
        if (is_resource($source)) {
            return @fclose($source);
        }

        return false;
    }

    /**
     * 重命名文件
     * @param string $filePath
     * @param string $newName
     * @return bool
     */
    public function rename($filePath, $newName)
    {
        return ocFileExists($filePath) ? @rename($filePath, $newName) : false;
    }

    /**
     * 删除文件
     * @param string $filePath
     * @return bool
     */
    public function delFile($filePath)
    {
        if ($filePath = ocFileExists($filePath)) {
            return is_writable($filePath) ? @unlink($filePath) : false;
        }

        return true;
    }

    /**
     * 读文件的所有内容
     * @param string $filePath
     * @return false|string
     * @throws Exception
     */
    public function readFile($filePath)
    {
        return ocRead($filePath);
    }

    /**
     * 向文件写入内容
     * @param string $path
     * @param string $content
     * @param int $perm
     * @param bool $trim
     * @return bool|false|int
     * @throws Exception
     */
    public function writeFile($path, $content, $perm = null, $trim = false)
    {
        return ocWrite($path, $this->getContent($content, $trim), false, $perm);
    }

    /**
     * 向文件追加一行
     * @param string $path
     * @param string $content
     * @param int $perm
     * @param bool $trim
     * @return bool|false|int
     * @throws Exception
     */
    public function appendFile($path, $content, $perm = null, $trim = false)
    {
        return ocWrite($path, $this->getContent($content, $trim), true, $perm);
    }

    /**
     * 复制文件
     * @param string $source
     * @param string $destination
     * @return bool
     * @throws Exception
     */
    public function copyFile($source, $destination)
    {
        $source = ocFileExists($source);

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
     * @throws Exception
     */
    public function moveFile($source, $destination)
    {
        $source = ocFileExists($source);

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
    public function fileInfo($filePath)
    {
        if (!ocFileExists($filePath)) return array();

        date_default_timezone_set('PRC');

        return array(
            'atime' => @date('Y-m-d h:i:s', fileatime($filePath)),
            'ctime' => @date('Y-m-d h:i:s', filectime($filePath)),
            'mtime' => @date('Y-m-d h:i:s', filemtime($filePath)),
            'perms' => @substr(sprintf("%o", fileperms($filePath)), -4),
            'size' => @filesize($filePath),
            'type' => @filetype($filePath)
        );
    }

    /**
     * 检查并新建目录
     * @param string $path
     * @param int $perm
     * @return bool
     * @throws Exception
     */
    public function createDir($path, $perm = null)
    {
        return ocCheckPath($path, $perm);
    }

    /**
     * 删除目录，支持递归删除
     * @param string $path
     * @param bool $recursive
     * @return bool
     */
    public function delDir($path, $recursive = false)
    {
        return $this->baseDelDir($path, $recursive, 'del');
    }

    /**
     * 清空目录，支持递归
     * @param string $path
     * @param bool $recursive
     * @return bool
     */
    public function clearDir($path, $recursive = false)
    {
        return $this->baseDelDir($path, $recursive, 'clear');
    }

    /**
     * 文件重命加一
     * @param string $file
     * @param string $separateSign
     * @return string
     */
    public function increaseFileName($file, $separateSign = '_')
    {
        $position = strrpos($file, '.');
        $mainName = substr($file, 0, $position);
        $extensionName = substr($file, $position);

        if (strstr($mainName, $separateSign)) {
            $options = explode($separateSign, $mainName);
            $lastKey = count($options) - 1;
            $options[$lastKey] = intval($options[$lastKey]) + 1;
            $mainName = implode($separateSign, $options);
        } else {
            $mainName = $mainName . $separateSign . '1';
        }

        $file = $mainName . ($extensionName ? '.' . $extensionName : OC_EMPTY);
        return $file;
    }

    /**
     * 类内部函数,删除或清空目录
     * @param string $path
     * @param bool $recursive
     * @param string $delType
     * @return bool
     */
    private function baseDelDir($path, $recursive = false, $delType = 'del')
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
                    if (!$this->delFile($subPath)) return false;
                } elseif (is_dir($subPath)) {
                    if (!$recursive) continue;
                    if (!$this->delDir($subPath, true, 'del')) return false;
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
     * @throws Exception
     */
    private function getContent($content, $trim)
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
