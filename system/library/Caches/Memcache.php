<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架    Memcache客户端插件Memcache
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Caches;

use Ocara\Core\CacheBase;
use Ocara\Interfaces\Cache as CacheInterface;

class Memcache extends CacheBase implements CacheInterface
{
	/**
	 * 初始化代码
	 * @param array $config
	 * @param bool $required
	 * @return null
	 */
	public function connect($config, $required = true)
	{
		if (!ocGet('open', $config, false)) {
			return ocService()->error->check('no_open_service_config', array('Memcache'), $required);
		}

		if (class_exists($class = 'Memcache', false)) {
			ocCheckExtension('memcache');
		} elseif (class_exists($class = 'Memcached', false)) {
			ocCheckExtension('memcached');
		} else {
			return ocService()->error->check('no_extension', array('Memcache'), $required);
		}

		$this->_plugin = new $class();
		$this->_addServers($config, $class);
	}

	/**
	 * 添加服务器
	 * @param array $config
	 * @param string $class
	 */
	private function _addServers($config, $class)
	{
		$servers = ocGet('servers', $config, array());
		
		if ($class == 'Memcached') {
			$this->_plugin->addServers($servers);
			if ($options = ocGet('options', $config, array())) {
				foreach ($options as $key => $value) {
					$this->_plugin->setOption($key, $value);
				}
			}
		} else {
			foreach ($servers as $serve) {
				call_user_func_array(
					array(&$this->_plugin, 'addServer'), $serve
				);
			}
		}
	}

    /**
     * 设置变量值
     * @param string $name
     * @param mixed $value
     */
    public function set($name, $value)
    {
        $args = func_get_args();
        $expireTime = array_key_exists(2, $args) ? $args[2] : 0;
        $params = array_key_exists(3, $args) ? $args[3] : array();

		if (i_object($this->_plugin)) {
			return $this->_plugin->set($name, $value, $params, $expireTime);
		}

		return false;
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
			return $this->_plugin->get($name);
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
		return true;
	}
}
