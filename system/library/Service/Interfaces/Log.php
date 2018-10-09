<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架    Log插件接口
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Service\Interfaces;

interface Log
{
	/**
	 * 选项设置函数
	 * @param string $logRoot
	 * @param integer $maxLogSize
	 */
	public function setOption($logRoot = null, $maxLogSize = null);

	/**
	 * 新建日志（目录）
	 * @param string $logName
	 */
	public function create($logName);

	/**
	 * 检测日志是否存在
	 * @param $logName
	 */
	public function has($logName);

	/**
	 * 向最近日志文件写入一行
	 * @param string $logName
	 * @param string $content
	 */
	public function write($logName, $content);

	/**
	 * 读取日志内容
	 * @param string $logName
	 */
	public function read($logName);

	/**
	 * 清理日志文件
	 * @param string $logName
	 */
	public function clear($logName);

	/**
	 * 删除日志（目录）
	 * @param string $logName
	 */
	public function delete($logName);

	/**
	 * 清空最近日志文件内容
	 * @param string $logName
	 */
	public function flush($logName);
}