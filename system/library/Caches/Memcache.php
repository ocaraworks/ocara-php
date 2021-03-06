<?php
/**
 * Memcache客户端类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Caches;

use Ocara\Exceptions\Exception;
use Ocara\Core\CacheBase;
use Ocara\Interfaces\Cache as CacheInterface;

class Memcache extends CacheBase implements CacheInterface
{
    /**
     * 初始化代码
     * @param array $config
     * @param bool $required
     * @return null
     * @throws Exception
     */
    public function connect($config, $required = true)
    {
        if (empty($config['servers'])) {
            ocService()->error->check('null_cache_host', array(), $required);
        }

        $isOpen = !empty($config['open']) ? $config['open'] : false;
        if (!$isOpen) {
            return ocService()->error->check('no_open_service_config', array('Memcache'), $required);
        }

        if (class_exists($class = 'Memcache', false)) {
            ocCheckExtension('memcache');
        } elseif (class_exists($class = 'Memcached', false)) {
            ocCheckExtension('memcached');
        } else {
            return ocService()->error->check('no_extension', array('Memcache'), $required);
        }

        $this->setPlugin(new $class());
        $this->addServers($config, $class);
    }

    /**
     * 添加服务器
     * @param array $config
     * @param string $class
     * @throws Exception
     */
    private function addServers(array $config, $class)
    {
        $plugin = $this->plugin();
        $servers = !empty($config['servers']) ? $config['servers'] : array();

        if ($class == 'Memcached') {
            $plugin->addServers($servers);
            $options = !empty($config['options']) ? $config['options'] : array();
            foreach ($options as $key => $value) {
                $plugin->setOption($key, $value);
            }
        } else {
            foreach ($servers as $serve) {
                call_user_func_array(
                    array(&$plugin, 'addServer'), $serve
                );
            }
        }
    }

    /**
     * 设置变量值
     * @param string $name
     * @param string $value
     * @param int $expireTime
     * @return bool|mixed
     * @throws Exception
     */
    public function set($name, $value, $expireTime = 0)
    {
        $args = func_get_args();
        $params = array_key_exists(3, $args) ? $args[3] : array();
        $plugin = $this->plugin(false);

        if (is_object($plugin)) {
            return $plugin->set($name, $value, $params, $expireTime);
        }

        return false;
    }

    /**
     * 获取变量值
     * @param string $name
     * @param null $args
     * @return string|null
     * @throws Exception
     */
    public function get($name, $args = null)
    {
        $plugin = $this->plugin(false);
        if (is_object($plugin) && method_exists($plugin, 'get')) {
            return $plugin->get($name);
        }

        return null;
    }

    /**
     * 删除KEY
     * @param string $name
     * @return mixed
     * @throws Exception
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
        return true;
    }
}
