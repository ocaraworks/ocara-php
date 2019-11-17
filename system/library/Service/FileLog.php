<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架    自定义log插件Log
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Service;

use Ocara\Core\ServiceBase;
use Ocara\Service\Interfaces\Log as LogInterface;
use Ocara\Exceptions\Exception;

class FileLog extends ServiceBase implements LogInterface
{
	public $logType;
	public $sysLogPath;
	public $logRoot;
	public $maxLogSize = 1;

    /**
     * 选项设置函数
     * @param string $logRoot
     * @param inte $maxLogSize
     * @throws Exception
     */
	public function setOption($logRoot = null, $maxLogSize = null)
	{
		if (empty($logRoot)) {
			$logRoot = ocConfig(array('LOG', 'root'), false);
		}
		
		if ($logRoot) {
			$this->logType = 'custom';
			$logRoot = ocPath('runtime', $logRoot);
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
		$this->maxLogSize = $maxLogSize ? : 2;
	}

    /**
     * 新建日志（目录）
     * @param string $logName
     * @return mixed
     */
	public function create($logName)
	{
		return ocService()->file->createDir($this->logRoot . $logName, 0777);
	}

    /**
     * 检测日志是否存在
     * @param $logName
     * @return bool
     */
	public function has($logName)
	{
		return is_dir($this->logRoot . $logName);
	}

    /**
     * 向最近日志文件写入一行
     * @param string $logName
     * @param string $content
     * @return mixed
     */
	public function write($logName, $content)
	{
		if(!ocScalar($content)){
			$content = ocJsonEncode($content);
		}

		if ($this->logType == 'sys') {
			return ocService()->file->appendFile($this->sysLogPath, "$content\n");
		}
		
		$logPath = $this->logRoot . $logName;
		if (!is_dir($logPath)) {
			$this->create($logPath);
		}
		
		$lastLogFile = ocDir($logPath) . $this->getLastLogFile($logName);
		$fileInfo 	 = ocService()->file->fileInfo($lastLogFile);

		if ($fileInfo && $fileInfo['size'] > $this->maxLogSize * 1024 * 1024) {
			$lastLogFile = $this->createLogFile($logName);
		}

		return ocService()->file->appendFile($lastLogFile, "{$content}" . PHP_EOL);
	}

    /**
     * 读取日志内容
     * @param string $logName
     * @return false|string|null
     */
	public function read($logName)
	{
		if ($this->logType == 'sys') {
			return ocRead($this->sysLogPath);
		}
		
		$file = $this->logRoot . ocDir($logName) . $this->getLastLogFile($logName);
		
		return ocFileExists($file) ? ocRead($file) : null;
	}

    /**
     * 清理日志文件
     * @param string $logName
     * @return bool
     */
	public function clear($logName = null)
	{
		$path = $this->logRoot . $logName;
		return is_dir($path) ? ocService()->file->clearDir($path, true) : false;
	}

    /**
     * 删除日志（目录）
     * @param string $logName
     * @return bool
     */
	public function delete($logName)
	{
		$path = $this->logRoot . $logName;
		return is_dir($path) ? ocService()->file->delDir($path, true) : false;
	}

    /**
     * 清空最近日志文件内容
     * @param string $logName
     * @return bool|int
     */
	public function flush($logName)
	{
		if ($this->logType == 'sys') {
			return ocWrite($this->sysLogPath, OC_EMPTY);
		}
		
		$path = $this->logRoot . ocDir($logName) . $this->getLastLogFile($logName);
		$path = ocFileExists($path, true);

		if ($path) {
			return ocWrite($path, OC_EMPTY);
		}

		return false;
	}

    /**
     * 获取最近日志文件
     * @param string $logName
     * @param bool $create
     * @return bool|mixed|string
     */
	protected function getLastLogFile($logName, $create = true)
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
		
		return $create ? ocBasename($this->createLogFile($logName)) : false;
	}

    /**
     * 新建日志文件
     * @param string $logName
     * @return mixed
     */
	protected function createLogFile($logName)
	{
		$path = $this->logRoot . ocDir($logName);
		return ocService()->file->createFile($path . $logName . '_' . date('YmdH') . '.txt');
	}
}