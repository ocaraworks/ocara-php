<?php
/**
 * 缓存工厂类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use Ocara\Exceptions\Exception;

class CacheFactory extends Base
{
    /**
     * 默认服务器名
     * @var string
     */
    protected $defaultServer = 'defaults';

    /**
     * 新建缓存实例
     * @param string $serverName
     * @param bool $required
     * @return mixed
     * @throws Exception
     */
    public function make($serverName = null, $required = true)
    {
        if (empty($serverName)) {
            $serverName = $this->defaultServer;
        }

        $object = $this->baseConnect($serverName, $required);
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
     * @return array|mixed
     * @throws Exception
     */
    public function getConfig($serverName = null)
    {
        if (empty($serverName)) {
            $serverName = $this->defaultServer;
        }

        $config = array();

        if (ocService()->resources->contain('cache.get_config')) {
            $config = ocService()
                ->resources
                ->get('cache.get_config')
                ->handle($serverName);
        }

        if (!$config) {
            $config = ocForceArray(ocConfig(array('CACHE', $serverName), array()));
        }

        if (!$config) {
            ocService()->error->show('not_exists_cache_config', array($serverName));
        }

        return $config;
    }

    /**
     * 连接缓存
     * @param string $serverName
     * @param bool $required
     * @return mixed
     * @throws Exception
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
    }
}