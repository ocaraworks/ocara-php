<?php
/**
 * 数据库工厂类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use \ReflectionException;
use Ocara\Exceptions\Exception;

class DatabaseFactory extends Base
{
    /**
     * 默认服务器名
     * @var string
     */
    protected $defaultServer = 'defaults';
    protected $connections = array();
    protected $databaseMaps = array(
        'Mysql' => 'Mysqli',
    );

    const EVENT_GET_CONFIG = 'getConfig';

    /**
     * @throws Exception
     */
    public function registerEvents()
    {
        $this->event(self::EVENT_GET_CONFIG)
            ->resource()
            ->append(ocConfig('RESOURCE.database.getConfig', null));
    }

    /**
     * 获取数据库实例
     * @param string $serverName
     * @param bool $master
     * @param bool $required
     * @return mixed|null
     * @throws Exception
     * @throws ReflectionException
     */
    public function make($serverName = null, $master = true, $required = true)
    {
        if (empty($serverName)) {
            $serverName = $this->defaultServer;
        }

        $database = $this->getDatabase($serverName, $master);

        if (is_object($database) && $database instanceof DatabaseBase) {
            return $database;
        }

        if ($required) {
            ocService()->error->show('not_exists_database', array($serverName));
        }

        return $database;
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
     * 获取数据库对象
     * @param string $serverName
     * @param bool $master
     * @return mixed|null
     * @throws Exception
     * @throws ReflectionException
     */
    protected function getDatabase($serverName, $master = true)
    {
        $config = $this->getConfig($serverName);
        $hosts = ocForceArray(ocDel($config, 'host'));

        if ($master) {
            $index = 0;
        } else {
            $index = 1;
            if (!isset($hosts[$index])) {
                $index = 0;
            }
        }

        $object = null;
        $name = $serverName . '_' . $index;

        if (isset($this->connections[$name]) && is_object($this->connections[$name])) {
            $object = $this->connections[$name];
        } else {
            if (isset($hosts[$index]) && $hosts[$index]) {
                $address = array_map('trim', explode(':', $hosts[$index]));
                $config['host'] = isset($address[0]) ? $address[0] : null;
                $config['port'] = isset($address[1]) ? $address[1] : null;
                $config['type'] = $this->getDatabaseType($config);
                $config['class'] = $config['type'];
                $config['connect_name'] = $name;
                $object = $this->createDatabase('Databases', $config);
                $this->connections[$name] = $object;
            }
        }

        return $object;
    }

    /**
     * 获取数据库配置信息
     * @param string $serverName
     * @return array|mixed
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
            $config = ocForceArray(ocConfig(array('DATABASE', $serverName), array()));
        }

        if (!$config) {
            ocService()->error->show('not_exists_database_config', array($serverName));
        }

        return $config;
    }

    /**
     * 获取数据库对象类名
     * @param array $config
     * @return string
     */
    public function getDatabaseType(array $config)
    {
        $type = isset($config['type']) ? ucfirst($config['type']) : OC_EMPTY;
        return isset($this->databaseMaps[$type]) ? $this->databaseMaps[$type] : $type;
    }

    /**
     * 获取数据库对象
     * @param $dir
     * @param $config
     * @return mixed
     * @throws Exception
     */
    protected function createDatabase($dir, $config)
    {
        $class = $config['class'] . 'Database';
        $classFile = $dir . OC_DIR_SEP . $class . '.php';
        $classInfo = ServiceBase::classFileExists($classFile);

        if ($classInfo) {
            list($path, $namespace) = $classInfo;
            include_once($path);
            $class = $namespace . 'Databases' . OC_NS_SEP . $class;
            if (class_exists($class)) {
                $object = new $class($config);
                return $object;
            }
        }

        ocService()->error->show('not_exists_database');
    }
}