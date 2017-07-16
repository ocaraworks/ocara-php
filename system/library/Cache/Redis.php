<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架    Redis客户端插件Redis
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Cache;
use Ocara\CacheBase;
use Ocara\Error;
use Ocara\Interfaces\Cache as CacheInterface;

class Redis extends CacheBase implements CacheInterface
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
	public function set($name, $value, $exireTime = 0, $args = null)
	{
		$result = $this->_plugin->set($name, serialize($value));
		$this->_plugin->setTimeout($name, $exireTime);
		return $result;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function get($name)
	{
		if (is_object($this->_plugin) && method_exists($this->_plugin, 'get')) {
			return unserialize($this->_plugin->get($name));
		}

		return null;
	}

	/**
	 * 删除KEY
	 * @param string $name
	 */
	public function delete($name)
	{
		return $this->_plugin->delete($name);
	}

	/**
	 * 选择数据库
	 * @param string $name
	 * @return bool
	 */
	public function selectDatabase($name)
	{
		return $this->_plugin->select($name);
	}
}
