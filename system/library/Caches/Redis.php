<?php
/**
 * Ocara开源框架 Redis客户端类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core\Caches;

use Ocara\Exceptions\Exception;
use Ocara\Core\CacheBase;
use Ocara\Interfaces\Cache as CacheInterface;

class Redis extends CacheBase implements CacheInterface
{
    protected $databaseName;
    protected $config;
    protected $defaultHost = '127.0.0.1';
    protected $defaultPort = 6379;

    /**
     * 连接数据库实例
     * @param array $config
     * @param bool $required
     * @return mixed
     * @throws Exception
     */
    public function connect($config, $required = true)
    {
        $this->config = $config;
        $isOpen = !empty($this->config['open']) ? $this->config['open'] : false;

        if (!$isOpen) {
            return ocService()->error->check('no_open_service_config', array('Redis'), $required);
        }

        if (!class_exists('Redis', false)) {
            return ocService()->error->check('no_extension', array('Redis'), $required);
        }

        ocCheckExtension('redis');

        $host = !empty($this->config['host']) ? $this->config['host'] : $this->defaultHost;
        $port = !empty($this->config['port']) ? $this->config['port'] : $this->defaultPort;

        $plugin = $this->baseConnect($host, $port, $required);
        $this->setPlugin($plugin);

        if (isset($this->config['name']) && !ocEmpty($this->config['name'])) {
            $this->selectDatabase($this->config['name']);
        }
    }

    /**
     * 立即连接
     * @param $host
     * @param $port
     * @param $required
     * @return \Redis|null
     * @throws Exception
     */
    public function baseConnect($host, $port, $required)
    {
        $plugin = null;
        $timeout = !empty($this->config['timeout']) ? $this->config['timeout'] : 0.0;
        $password = !empty($this->config['password']) ? $this->config['password'] : 0.0;

        if (empty($host)) {
            return ocService()->error->check('null_cache_host', array(), $required);
        }

        try {
            $plugin = new \Redis();
            $plugin->connect($host, $port, $timeout);
        } catch (\Exception $exception) {
            ocService()->error->writeLog($exception->getMessage());
            ocService()->error->show('Redis连接失败！');
        }

        if ($password) {
            $auth = $plugin->auth($password);
            if (empty($auth)) {
                return ocService()->error->check('fault_redis_password', array(), $required);
            }
        }

        return $plugin;
    }

    /**
     * 设置变量值
     * @param string $name
     * @param bool $value
     * @param int $expireTime
     * @return bool
     */
    public function set($name, $value, $expireTime = 0)
    {
        $plugin = $this->plugin();

        if ($expireTime > 0) {
            $result = $plugin->setex($name, $expireTime, $value);
        } else {
            $result = $plugin->set($name, $value);
        }

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
            return $plugin->get($name);
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
     * @param string $databaseName
     * @return mixed
     */
    public function selectDatabase($databaseName)
    {
        $this->databaseName = $databaseName;
        return $this->plugin()->select($this->databaseName);
    }
}
