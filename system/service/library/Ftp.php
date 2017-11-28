<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架    FTP服务插件Ftp
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Service;
use Ocara\Ocara;
use Ocara\ServiceBase;

class Ftp extends ServiceBase
{
	public $root;
	protected $_conn;
	
	/**
	 * FTP连接
	 * @param string $ftpserver
	 * @param string $username
	 * @param string $password
	 * @param integer $port
	 * @param integer $timeOut
	 */
	public function connect($ftpserver, $username, $password, $port = false, $timeOut = false)
	{
		$port = $port ? : 21;
		
		if (!$ftpserver) {
			$this->showError('no_address');
		}
		
		$conn = @ftp_connect($ftpserver, $port);
		
		if (!$conn) {
			$this->showError('failed_ftp_connect');
		}
		
		if (!@ftp_login($conn, $username, $password)) {
			$this->showError('failed_ftp_login');
		}
		
		$this->_conn = $conn;
		$this->root = @ftp_pwd($this->_conn);
		
		if ($timeOut && preg_match('/^[0-9]+$/', $timeOut)) {
			@ftp_set_option($this->_conn, FTP_TIMEOUT_SEC, $timeOut);
		}
	}

	/**
	 * 上传文件
	 * @param string $localFile
	 * @param string $remoteFile
	 * @param bool $asyn
	 * @param string $mode,'a' is FTP_ASCII,'b' is FTP_BINARY
	 * @param integer $location
	 */
	public function upload($localFile, $remoteFile, $asyn = true, $mode = 'b', $location = 0)
	{
		$mode = $mode == 'b' ? FTP_BINARY : FTP_ASCII;
		
		if (!$asyn) {
			return @ftp_put($this->_conn, $remoteFile, $localFile, $mode);
		}
		
		$location = $location ? : 0;

		$result = @ftp_nb_put($this->_conn, $remoteFile, $localFile, $mode, $location);
		
		while ($result == FTP_MOREDATA) {
			$result = @ftp_nb_continue($this->_conn);
		}
		
		return $result == FTP_FINISHED ? true : false;
	}

	/**
	 * 下载文件
	 * @param string $remoteFile
	 * @param string $localFile
	 * @param bool $asyn
	 * @param string $mode
	 * @param integer $location
	 */
	public function download($remoteFile, $localFile, $asyn = true, $mode = 'b', $location = 0)
	{
		$fopen = @fopen($localFile, 'wb');
		
		if (!$fopen) {
			$this->showError('no_write_access');
		}
		
		$mode = $mode == 'b' ? FTP_BINARY : FTP_ASCII;
		
		if (!$asyn) {
			return @ftp_get($this->_conn, $localFile, $remoteFile, $mode);
		}
		
		$location = $location ? : 0;

		$result = @ftp_nb_get($this->_conn, $localFile, $remoteFile, $mode, $location);
		
		while ($result == FTP_MOREDATA) {
			$result = @ftp_nb_continue($this->_conn);
		}
		
		return $result == FTP_FINISHED ? true : false;
	}

	/**
	 * 删除文件
	 * @param string $path
	 */
	public function delFile($path)
	{
		return @ftp_delete($this->_conn, $path);
	}

	/**
	 * 删除FTP目录，支持递归删除
	 * @param string $dirName
	 * @param bool $recursive
	 * @param string $path
	 */
	public function delDir($dirName, $recursive = false, $path = false)
	{
		$pwd 	 = ocDir($this->getPwd());
		$path 	 = !$path ? $pwd : $pwd . ltrim(ocDir($path), OC_DIR_SEP);
		$allPath = $path . $dirName;
		
		if (!$this->chDir($allPath)) {
			return true;
		}
		
		$this->chDir($pwd);
		
		if (!$recursive) {
			return @ftp_rmdir($this->_conn, $allPath);
		}
		
		$subFiles = $this->listDir($allPath);
		$result = false;
		if (!$subFiles) {
			return @ftp_rmdir($this->_conn, $allPath);
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
		return $result ? @ftp_rmdir($this->_conn, $dir) : false;
	}

	/**
	 * 新建FTP目录
	 * @param string $dirName
	 * @param Ox integer $perm
	 */
	public function createDir($dirName, $perm = false)
	{
		$path = ocDir($this->getPwd());
		$result = @ftp_mkdir($this->_conn, $path . $dirName);
		
		if ($perm) {
			if (!chmod($path, $perm)) {
				$this->showError('no_modify_mode_acess', array($dirName, $perm));
			}
		}
		
		return $result;
	}

	/**
	 * 获取当前所在FTP目录
	 */
	public function getPwd()
	{
		return @ftp_pwd($this->_conn);
	}

	/**
	 * 列出FTP目录内容
	 * @param string $path
	 */
	public function listDir($path = null)
	{
		if (!$path) {
			$path = @ftp_pwd($this->_conn);
		}
		
		return @ftp_nlist($this->_conn, ocDir($path));
	}

	/**
	 * 改变FTP路径
	 * @param string $path
	 */
	public function chDir($path)
	{
		Ocara::errorReporting(E_ALL ^ E_WARNING);
		$result = @ftp_chdir($this->_conn, $path);
		Ocara::errorReporting();
		
		return $result;
	}

	/**
	 * 检查并新建不存在FTP目录
	 * @param string $path
	 */
	public function checkDir($path)
	{
		$pathArray = explode(OC_DIR_SEP, str_replace('\\', OC_DIR_SEP, $path));
		$pwd = $this->getPwd();
		
		Ocara::errorReporting(E_ALL ^ E_WARNING);
		
		foreach ($pathArray as $dir) {
			if ($dir == '.' || $dir == '..') $dir .= OC_DIR_SEP;
			if (!@ftp_chdir($this->_conn, $dir)) {
				$mk = @ftp_mkdir($this->_conn, $dir);
				if (!$mk) return false;
				if (!@ftp_chdir($this->_conn, $dir)) return false;
			}
		}
		
		@ftp_chdir($this->_conn, $pwd);
		Ocara::errorReporting();

		return true;
	}

	/**
	 * 获取FTP文件大小
	 * @param string $remoteFile
	 */
	public function getSize($remoteFile)
	{
		return @ftp_size($this->_conn, $remoteFile);
	}

	/**
	 * 重命名FTP文件
	 * @param string $oldName
	 * @param string $newname
	 */
	public function rename($oldName, $newName)
	{
		return @ftp_rename($this->_conn, $oldName, $newName);
	}

	/**
	 * 执行命令
	 * @param string $command
	 */
	public function execute($command)
	{
		return @ftp_exec($this->_conn, $command);
	}

	/**
	 * 关闭FTP连接
	 */
	public function close()
	{
		return @ftp_close($this->_conn);
	}
}
