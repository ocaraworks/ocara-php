<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架    Redis客户端插件Redis
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core\Caches;

use Ocara\Core\CacheBase;
use Ocara\Interfaces\Cache as CacheInterface;

class Redis extends CacheBase implements CacheInterface
{
	public function connect($config, $required = true)
	{
		if (!ocGet('open', $config, false)) {
			return ocService()->error->check('no_open_service_config', array('Redis'), $required);
		}

		if (!class_exists('Redis', false)) {
			return ocService()->error->check('no_extension', array('Redis'), $required);
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
				return ocService()->error->check('fault_redis_password', array(), $required);
			}
		}
	}

    /**
     * 设置变量值
     * @param string $name
     * @param bool $value
     * @return bool
     */
    public function set($name, $value)
    {
        $args = func_get_args();
        $expireTime = array_key_exists(2, $args) ? $args[2] : 0;

		$result = $this->_plugin->set($name, serialize($value));
		$this->_plugin->setTimeout($name, $expireTime);
		return $result;
	}

    /**
     * 获取变量值
     * @param string $name
     * @param mixed $args
     * @return null
     */
    public function get($name, $args = null)
    {
		if (is_object($this->_plugin) && method_exists($this->_plugin, 'get')) {
			return unserialize($this->_plugin->get($name));
		}

		return null;
	}

    /**
     * 删除KEY
     * @param string $name
     * @return mixed
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
