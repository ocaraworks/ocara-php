<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 缓存客户端接口基类CacheBase
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

class CacheBase extends Base
{
	protected $_plugin;
	protected $_connectName;

	protected $_slaves = array();
	protected $_config = array();

	private static $_connects = array();

	/**
	 * 初始化方法
	 * CacheBase constructor.
	 * @param array $config
	 * @param bool $required
	 */
	public function __construct(array $config, $required = true)
	{
		$connectName = $config['connect_name'];
		$this->setConnectName($connectName);

		if (isset(self::$_connects[$connectName]) && self::$_connects[$connectName] instanceof CacheBase) {
			$this->_plugin = self::$_connects[$connectName];
		} else {
			$this->connect($config, $required);
			self::$_connects[$connectName] = $this->_plugin;
		}
	}

	/**
	 * 设置连接名称
	 * @param $connectName
	 */
	public function setConnectName($connectName)
	{
		$this->_connectName = $connectName;
	}

	/**
	 * 获取连接名称
	 * @return mixed
	 */
	public function getConnectName()
	{
		return $this->_connectName;
	}
}