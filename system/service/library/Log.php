<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架    自定义log插件Log
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Service;
use Ocara\ServiceBase;

class Log extends ServiceBase
{
	public $logType;
	public $sysLogPath;
	public $logRoot;
	public $maxLogSize = 1;

	/**
	 * 选项设置函数
	 * @param string $logRoot
	 * @param integer $maxLogSize
	 */
	public function setOption($logRoot = false, $maxLogSize = false)
	{
		if (empty($logRoot)) {
			$logRoot = ocConfig('LOG_PATH', false);
		}
		
		if ($logRoot) {
			$this->logType = 'mine';
			$logRoot = ocPath('data', $logRoot);
		}
		
		if (empty($logRoot) && $path = @ini_get('error_log')) {
			$this->logType = 'sys';
			$this->sysLogPath = $path;
			$logRoot = dirname($this->sysLogPath);
		}
		
		if (empty($logRoot)) {
			$this->showError('not_exists_log_dir');
		}
		
		if (!ocCheckPath($logRoot)) {
			$this->showError('cannot_create_log_dir');
		}
		
		$this->logRoot 	  = ocDir($logRoot);
		$this->maxLogSize = $maxLogSize ? $maxLogSize : 2;
	}

	/**
	 * 新建日志（目录）
	 * @param string $logName
	 */
	public function create($logName)
	{
		return File::createDir($this->logRoot . $logName, 0777);
	}

	/**
	 * 检测日志是否存在
	 * @param $logName
	 */
	public function exists($logName)
	{
		return is_dir($this->logRoot . $logName);
	}

	/**
	 * 向最近日志文件写入一行
	 * @param string $logName
	 * @param string $content
	 */
	public function write($logName, $content)
	{
		if(!ocScalar($content)){
			$content = json_encode($content);
		}

		if ($this->logType == 'sys') {
			return File::appendFile($this->sysLogPath, "$content\n");
		}
		
		$logPath = $this->logRoot . $logName;
		if (!is_dir($logPath)) {
			$this->create($logPath);
		}
		
		$lastLogFile = ocDir($logPath) . $this->_getLastLogFile($logName);
		$fileInfo 	 = File::fileInfo($lastLogFile);

		if ($fileInfo && $fileInfo['size'] > $this->maxLogSize * 1024 * 1024) {
			$lastLogFile = $this->_createLogFile($logName);
		}

		return File::appendFile($lastLogFile, "{$content}" . OC_ENTER);
	}

	/**
	 * 读取日志内容
	 * @param string $logName
	 */
	public function read($logName)
	{
		if ($this->logType == 'sys') {
			return ocRead($this->sysLogPath);
		}
		
		$file = $this->logRoot . ocDir($logName) . $this->_getLastLogFile($logName);
		
		return ocFileExists($file) ? ocRead($file) : null;
	}

	/**
	 * 清理日志文件
	 * @param string $logName
	 */
	public function clear($logName)
	{
		$path = $this->logRoot . $logName;
		return is_dir($path) ? File::clearDir($path, true) : false;
	}

	/**
	 * 删除日志（目录）
	 * @param string $logName
	 */
	public function del($logName)
	{
		$path = $this->logRoot . $logName;
		return is_dir($path) ? File::delDir($path, true) : false;
	}

	/**
	 * 清空最近日志文件内容
	 * @param string $logName
	 */
	public function flush($logName)
	{
		if ($this->logType == 'sys') {
			return ocWrite($this->sysLogPath, OC_EMPTY);
		}
		
		$path = $this->logRoot . ocDir($logName) . $this->_getLastLogFile($logName);
		$path = ocFileExists($path, true);

		if ($path) {
			return ocWrite($path, OC_EMPTY);
		}

		return false;
	}

	/**
	 * 获取最近日志文件
	 * @param string  $logName
	 * @param bool $create
	 */
	protected function _getLastLogFile($logName, $create = true)
	{
		$logPath = $this->logRoot . ocDir($logName);
		
		if (!is_dir($logPath)) {
			$this->create($logPath);
		}
		
		$max 	= 0;
		$regExp = '/^.+\_([0-9]+)\.[a-z0-9]{2,4}$/i';
		$files 	= scandir($logPath);
		
		ocDel($files, 0, 1);
		
		if ($files) {
			foreach ($files as $file) {
				if (preg_match($regExp, $file, $mt)) {
					if ($mt[1] > $max) $max = $mt[1];
				}
			}
		}

		if ($max) {
			return $logName . '_' . $max . '.txt';
		}
		
		return $create ? ocBasename($this->_createLogFile($logName)) : false;
	}

	/**
	 * 新建日志文件
	 * @param string $logName
	 */
	protected function _createLogFile($logName)
	{
		$path = $this->logRoot . ocDir($logName);
		return File::createFile($path . $logName . '_' . time() . '.txt');
	}
}