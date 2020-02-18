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
	public $extensionName;

    /**
     * 选项设置函数
     * @param string $logRoot
     * @param int $maxLogSize
     * @param string $extensionName
     * @throws Exception
     */
	public function setOption($logRoot = null, $maxLogSize = null, $extensionName = null)
	{
        $extensionName = $extensionName ? $extensionName : 'txt';

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

		$this->logRoot = ocDir($logRoot);
		$this->maxLogSize = $maxLogSize ? : 2;
		$this->extensionName = $extensionName;
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

		$lastLogFile = $this->getLastLogFile($logName);
		$result = ocService()->file->appendFile($lastLogFile, "{$content}" . PHP_EOL);

		return $result;
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

		$maxFile = OC_EMPTY;
		$files = scandir($logPath, defined('SCANDIR_SORT_DESCENDING') ? SCANDIR_SORT_DESCENDING : 1);

        foreach ($files as $file) {
            $logFile = $logPath . $file;
            if (is_file($logFile)) {
                $pathInfo = pathinfo($logFile);
                if ($pathInfo['extension'] == $this->extensionName) {
                    $maxFile = $file;
                    break;
                }
            }
        }

        $fileService = ocService()->file;
        $todayFile = date('Ymd') . '.' . $this->extensionName;

		if (!$maxFile && $maxFile < $todayFile) {
            $maxFile = $todayFile;
        } else {
            $fileInfo = $fileService->fileInfo($logPath . $maxFile);
            $maxSize = $this->maxLogSize * 1024 * 1024;
            if ($fileInfo && $fileInfo['size'] > $maxSize) {
                $maxFile = $fileService->increaseFileName($maxFile);
            }
        }

		$filePath = $logPath . $maxFile;

		if ($create && !is_file($filePath)) {
            $fileService->createFile($filePath, 0777, 'wb');
        }

		return $filePath;
	}
}