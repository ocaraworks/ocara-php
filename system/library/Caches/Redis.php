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
    /**
     * 连接数据库实例
     * @param array $config
     * @param bool $required
     * @return mixed
     */
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

		if (empty($host)) {
            return ocService()->error->check('null_cache_host', array(), $required);
        }

        $plugin = $this->setPlugin(new \Redis());
        $plugin->connect($host, $port, $timeout);

		if ($password) {
			$auth = $plugin->auth($password);
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
        $plugin = $this->plugin();

		$result = $plugin->set($name, serialize($value));
        $plugin->setTimeout($name, $expireTime);
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
        $plugin = $this->plugin(false);
		if (is_object($plugin) && method_exists($plugin, 'get')) {
			return unserialize($plugin->get($name));
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
		return $this->plugin()->delete($name);
	}

	/**
	 * 选择数据库
	 * @param string $name
	 * @return bool
	 */
	public function selectDatabase($name)
	{
		return $this->plugin()->select($name);
	}
}
