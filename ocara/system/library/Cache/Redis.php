<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架    Redis客户端插件Cache_OCRedis
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Cache;
use Ocara\CacheBase;
use Ocara\Error;
use Ocara\Interfaces\Cache as CacheInterface;

class OCRedis extends CacheBase implements CacheInterface
{
	protected $_plugin = null;
	private $_config = array();

	/**
	 * OCRedis constructor.
	 * @param array $config
	 * @param bool $required
	 */
	public function __construct($config, $required = true)
	{
		$this->_config = $config;

		if (!ocGet('open', $config, false)) {
			return Error::check('no_open_service_config', array('Redis'), $required);
		}

		if (!class_exists('Redis', false)) {
			return Error::check('no_extension', array('Redis'), $required);
		}

		ocCheckExtension('redis');

		$host 	  = ocGet('host', $config);
		$port 	  = ocGet('port', $config, 6379);
		$timeout  = ocGet('timeout', $config, false);
		$password = ocGet('password', $config, false);
		
		$this->_plugin = new Redis();
		$this->_plugin->connect($host, $port, $timeout);

		if ($password) {
			$auth = $this->_plugin->auth($password);
			if (empty($auth)) {
				return Error::check(
					'fault_redis_password', array(), $required
				);
			}
		}
	}

	/**
	 * @param string $name
	 * @param boolean $value
	 * @param integer $exireTime
	 * @param mixed $args
	 * @return bool
	 */
	public function setVar($name, $value, $exireTime = 0, $args = null)
	{
		$result = $this->_plugin->set($name, serialize($value));
		$this->_plugin->setTimeout($name, $exireTime);
		return $result;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getVar($name)
	{
		return unserialize($this->_plugin->set($name));
	}

	/**
	 * 删除KEY
	 * @param string $name
	 */
	public function deleteVar($name)
	{
		return $this->_plugin->delete($name);
	}
}
