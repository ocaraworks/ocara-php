<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架    FTP服务插件Ftp
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Service;

use Ocara\Core\Ocara;
use Ocara\Core\ServiceBase;
use Ocara\Exceptions\Exception;

class Ftp extends ServiceBase
{
    public $root;
    protected $connection;

    /**
     * FTP连接
     * @param $ftpServer
     * @param $username
     * @param $password
     * @param null $port
     * @param null $timeOut
     * @throws Exception
     */
    public function connect($ftpServer, $username, $password, $port = null, $timeOut = null)
    {
        $port = $port ?: 21;

        if (!$ftpServer) {
            $this->showError('no_address');
        }

        $connection = @ftp_connect($ftpServer, $port);

        if (!$connection) {
            $this->showError('failed_ftp_connect');
        }

        if (!@ftp_login($connection, $username, $password)) {
            $this->showError('failed_ftp_login');
        }

        $this->connection = $connection;
        $this->root = @ftp_pwd($this->connection);

        if ($timeOut && preg_match('/^[0-9]+$/', $timeOut)) {
            @ftp_set_option($this->connection, FTP_TIMEOUT_SEC, $timeOut);
        }
    }

    /**
     * 上传文件
     * @param $localFile
     * @param $remoteFile
     * @param bool $async
     * @param string $mode
     * @param int $location
     * @return bool
     */
    public function upload($localFile, $remoteFile, $async = true, $mode = 'b', $location = 0)
    {
        $mode = $mode == 'b' ? FTP_BINARY : FTP_ASCII;

        if (!$async) {
            return @ftp_put($this->connection, $remoteFile, $localFile, $mode);
        }

        $location = $location ?: 0;

        $result = @ftp_nb_put($this->connection, $remoteFile, $localFile, $mode, $location);

        while ($result == FTP_MOREDATA) {
            $result = @ftp_nb_continue($this->connection);
        }

        return $result == FTP_FINISHED ? true : false;
    }

    /**
     * 下载文件
     * @param $remoteFile
     * @param $localFile
     * @param bool $async
     * @param string $mode
     * @param int $location
     * @return bool
     * @throws Exception
     */
    public function download($remoteFile, $localFile, $async = true, $mode = 'b', $location = 0)
    {
        $fopen = @fopen($localFile, 'wb');

        if (!$fopen) {
            $this->showError('no_write_access');
        }

        $mode = $mode == 'b' ? FTP_BINARY : FTP_ASCII;

        if (!$async) {
            return @ftp_get($this->connection, $localFile, $remoteFile, $mode);
        }

        $location = $location ?: 0;

        $result = @ftp_nb_get($this->connection, $localFile, $remoteFile, $mode, $location);

        while ($result == FTP_MOREDATA) {
            $result = @ftp_nb_continue($this->connection);
        }

        return $result == FTP_FINISHED ? true : false;
    }

    /**
     * 删除文件
     * @param $path
     * @return bool
     */
    public function delFile($path)
    {
        return @ftp_delete($this->connection, $path);
    }

    /**
     * 删除FTP目录，支持递归删除
     * @param $dirName
     * @param bool $recursive
     * @param null $path
     * @return bool
     */
    public function delDir($dirName, $recursive = false, $path = null)
    {
        $pwd = ocDir($this->getPwd());
        $path = !$path ? $pwd : $pwd . ltrim(ocDir($path), OC_DIR_SEP);
        $allPath = $path . $dirName;

        if (!$this->chDir($allPath)) {
            return true;
        }

        $this->chDir($pwd);

        if (!$recursive) {
            return @ftp_rmdir($this->connection, $allPath);
        }

        $subFiles = $this->listDir($allPath);
        $result = false;
        if (!$subFiles) {
            return @ftp_rmdir($this->connection, $allPath);
        }

        foreach ($subFiles as $val) {
            $ch = $this->chDir($allPath . OC_DIR_SEP . $val);
            $this->chDir($pwd);
            if (!$ch) {
                $result = @$this->delFile($allPath . OC_DIR_SEP . $val);
            } else {
                $result = $this->delDir(ltrim($path, OC_DIR_SEP) . $dirName . OC_DIR_SEP . $val, true, $pwd);
            }
        }

        $dir = ltrim($path, OC_DIR_SEP) . OC_DIR_SEP . $dirName;
        return $result ? @ftp_rmdir($this->connection, $dir) : false;
    }

    /**
     * 新建FTP目录
     * @param $dirName
     * @param null $perm
     * @return string
     * @throws Exception
     */
    public function createDir($dirName, $perm = null)
    {
        $path = ocDir($this->getPwd());
        $result = @ftp_mkdir($this->connection, $path . $dirName);

        if ($perm) {
            if (!chmod($path, $perm)) {
                $this->showError('no_modify_mode_access', array($dirName));
            }
        }

        return $result;
    }

    /**
     * 获取当前所在FTP目录
     * @return string
     */
    public function getPwd()
    {
        return @ftp_pwd($this->connection);
    }

    /**
     * 列出FTP目录内容
     * @param null $path
     * @return array
     */
    public function listDir($path = null)
    {
        if (!$path) {
            $path = @ftp_pwd($this->connection);
        }

        return @ftp_nlist($this->connection, ocDir($path));
    }

    /**
     * 改变FTP路径
     * @param $path
     * @return bool
     */
    public function chDir($path)
    {
        Ocara::errorReporting(E_ALL ^ E_WARNING);
        $result = @ftp_chdir($this->connection, $path);
        Ocara::errorReporting();

        return $result;
    }

    /**
     * 检查并新建不存在FTP目录
     * @param $path
     * @return bool
     */
    public function checkDir($path)
    {
        $pathArray = explode(OC_DIR_SEP, str_replace('\\', OC_DIR_SEP, $path));
        $pwd = $this->getPwd();

        Ocara::errorReporting(E_ALL ^ E_WARNING);

        foreach ($pathArray as $dir) {
            if ($dir == '.' || $dir == '..') $dir .= OC_DIR_SEP;
            if (!@ftp_chdir($this->connection, $dir)) {
                $mk = @ftp_mkdir($this->connection, $dir);
                if (!$mk) return false;
                if (!@ftp_chdir($this->connection, $dir)) return false;
            }
        }

        @ftp_chdir($this->connection, $pwd);
        Ocara::errorReporting();

        return true;
    }

    /**
     * 获取FTP文件大小
     * @param $remoteFile
     * @return int
     */
    public function getSize($remoteFile)
    {
        return @ftp_size($this->connection, $remoteFile);
    }

    /**
     * 重命名FTP文件
     * @param $oldName
     * @param $newName
     * @return bool
     */
    public function rename($oldName, $newName)
    {
        return @ftp_rename($this->connection, $oldName, $newName);
    }

    /**
     * 执行命令
     * @param $command
     * @return bool
     */
    public function execute($command)
    {
        return @ftp_exec($this->connection, $command);
    }

    /**
     * 关闭FTP连接
     */
    public function close()
    {
        return @ftp_close($this->connection);
    }
}
