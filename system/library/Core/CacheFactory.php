<?php
/**
 * 缓存工厂类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class CacheFactory extends Base
{
    /**
     * 默认服务器名
     * @var string
     */
    protected static $defaultServer = 'defaults';

    /**
     * 新建缓存实例
     * @param string $connectName
     * @param bool $required
     * @return mixed
     * @throws Exception
     */
    public static function getInstance($connectName = null, $required = true)
    {
        if (empty($connectName)) {
            $connectName = self::$defaultServer;
        }

        $object = self::baseConnect($connectName, $required);
        if (is_object($object) && $object instanceof CacheBase) {
            return $object;
        }

        return ocService()->error
            ->check('not_exists_cache', array($connectName), $required);
    }

    /**
     * 获取默认服务器名称
     * @return string
     */
    public static function getDefaultServer()
    {
        return self::$defaultServer;
    }

    /**
     * 获取配置信息
     * @param string $connectName
     * @return array|mixed
     * @throws Exception
     */
    public static function getConfig($connectName = null)
    {
        if (empty($connectName)) {
            $connectName = self::$defaultServer;
        }

        $config = array();

        if ($callback = ocConfig(array('RESOURCE', 'cache', 'get_config'), null)) {
            $config = call_user_func_array($callback, array($connectName));
        }

        if (!$config) {
            $config = ocForceArray(ocConfig(array('CACHE', $connectName), array()));
        }

        if (!$config) {
            ocService()->error->show('not_exists_cahce_config', array($connectName));
        }

        return $config;
    }

    /**
     * 连接缓存
     * @param string $connectName
     * @param bool $required
     * @return mixed
     * @throws Exception
     */
    private static function baseConnect($connectName, $required = true)
    {
        $config = self::getConfig($connectName);
        $type = ucfirst(ocConfig(array('CACHE', $connectName, 'type')));

        $classInfo = ServiceBase::classFileExists("Caches/{$type}.php");
        if ($classInfo) {
            list($path, $namespace) = $classInfo;
            include_once($path);
            $class = $namespace . 'Core\Caches' . OC_NS_SEP . $type;
            if (class_exists($class)) {
                $config['connect_name'] = $connectName;
                $object = new $class($config, $required);
                return $object;
            }
        }
    }
}