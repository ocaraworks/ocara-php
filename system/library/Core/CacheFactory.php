<?php
/**
 * 缓存工厂类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use \ReflectionException;
use Ocara\Exceptions\Exception;

class CacheFactory extends Base
{
    /**
     * 默认服务器名
     * @var string
     */
    protected $defaultServer = 'defaults';
    protected $connections = array();

    const EVENT_GET_CONFIG = 'getConfig';

    /**
     * @throws Exception
     */
    public function registerEvents()
    {
        $this->event(self::EVENT_GET_CONFIG)
            ->resource()
            ->append(ocConfig('RESOURCE.cache.get_config', null));
    }

    /**
     * 新建缓存实例
     * @param string $serverName
     * @param bool $required
     * @return object|null
     * @throws Exception
     * @throws ReflectionException
     */
    public function make($serverName = null, $required = true)
    {
        if (empty($serverName)) {
            $serverName = $this->defaultServer;
        }

        $object = $this->getCache($serverName, $required);
        if (is_object($object) && $object instanceof CacheBase) {
            return $object;
        }

        return ocService()->error->check('not_exists_cache', array($serverName), $required);
    }

    /**
     * 获取默认服务器名称
     * @return string
     */
    public function getDefaultServer()
    {
        return $this->defaultServer;
    }

    /**
     * 获取配置信息
     * @param string $serverName
     * @return array
     * @throws Exception
     * @throws ReflectionException
     */
    public function getConfig($serverName = null)
    {
        if (empty($serverName)) {
            $serverName = $this->defaultServer;
        }

        $config = $this->fire(self::EVENT_GET_CONFIG, array($serverName));

        if (!$config) {
            $config = ocForceArray(ocConfig(array('CACHE', $serverName), array()));
        }

        if (!$config) {
            ocService()->error->show('not_exists_cache_config', array($serverName));
        }

        return $config;
    }

    /**
     * 获取缓存实例
     * @param $serverName
     * @param bool $required
     * @return mixed|object|null
     * @throws Exception
     * @throws ReflectionException
     */
    protected function getCache($serverName, $required = true)
    {
        if (isset($this->connections[$serverName]) && is_object($this->connections[$serverName])) {
            $object = $this->connections[$serverName];
        } else {
            $object = $this->baseConnect($serverName, $required);
            if ($object) {
                $this->connections[$serverName] = $object;
            }
        }

        return $object;
    }

    /**
     * 连接缓存
     * @param string $serverName
     * @param bool $required
     * @return object|null
     * @throws Exception
     * @throws ReflectionException
     */
    private function baseConnect($serverName, $required = true)
    {
        $config = $this->getConfig($serverName);
        $type = ucfirst(ocConfig(array('CACHE', $serverName, 'type')));
        $classInfo = ServiceBase::classFileExists("Caches/{$type}.php");

        if ($classInfo) {
            list($path, $namespace) = $classInfo;
            include_once($path);
            $class = $namespace . 'Core\Caches' . OC_NS_SEP . $type;
            if (class_exists($class)) {
                $config['connect_name'] = $serverName;
                $object = new $class($config, $required);
                return $object;
            }
        }

        return null;
    }
}