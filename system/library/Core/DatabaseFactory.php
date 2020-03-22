<?php
/**
 
 * Ocara开源框架 数据库接口类Database
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class DatabaseFactory extends Base
{
    /**
     * 默认服务器名
     * @var string
     */
    protected static $defaultServer = 'defaults';
    protected static $connections = array();
    protected static $databaseMaps = array(
        'Mysql' => 'Mysqli',
    );

    /**
     * 获取数据库实例
     * @param string $connectName
     * @param bool $master
     * @param bool $required
     * @return mixed|null
     * @throws Exception
     */
    public static function getInstance($connectName = null, $master = true, $required = true)
    {
        if (empty($connectName)) {
            $connectName = self::$defaultServer;
        }

        $database = self::getDatabase($connectName, $master);
        if (is_object($database) && $database instanceof DatabaseBase) {
            return $database;
        }

        if ($required) {
            ocService()->error->show('not_exists_database', array($connectName));
        }

        return $database;
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
     * 获取数据库对象
     * @param $connectName
     * @param bool $master
     * @return mixed|null
     * @throws Exception
     */
    private static function getDatabase($connectName, $master = true)
    {
        $config = self::getConfig($connectName);
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
        $name = $connectName . '_' . $index;

        if (isset(self::$connections[$name]) && is_object(self::$connections[$name])) {
            $object = self::$connections[$name];
        } else {
            if (isset($hosts[$index]) && $hosts[$index]) {
                $address = array_map('trim', explode(':', $hosts[$index]));
                $config['host'] = isset($address[0]) ? $address[0] : null;
                $config['port'] = isset($address[1]) ? $address[1] : null;
                $config['type'] = self::getDatabaseType($config);
                $config['class'] = $config['type'];
                $config['connect_name'] = $name;
                $object = self::createDatabase('Databases', $config);
                self::$connections[$name] = $object;
            }
        }

        return $object;
    }

    /**
     * 获取数据库配置信息
     * @param null $connectName
     * @return array|mixed
     * @throws Exception
     */
    public static function getConfig($connectName = null)
    {
        if (empty($connectName)) {
            $connectName = self::$defaultServer;
        }

        $config = ocForceArray(ocConfig(array('DATABASE', $connectName), array()));

        if (!$config) {
            ocService()->error->show('not_exists_database_config', array($connectName));
        }

        if ($callback = ocConfig(array('RESOURCE', 'database', 'get_config'), null)) {
            $config = array_merge(
                $config,
                call_user_func_array($callback, array($connectName))
            );
        }

        return $config;
    }

    /**
     * 获取数据库对象类名
     * @param array $config
     * @return string
     */
    public static function getDatabaseType(array $config)
    {
        $type = isset($config['type']) ? ucfirst($config['type']) : OC_EMPTY;
        return isset(self::$databaseMaps[$type]) ? self::$databaseMaps[$type] : $type;
    }

    /**
     * 获取数据库对象
     * @param $dir
     * @param $config
     * @return mixed
     * @throws Exception
     */
    private static function createDatabase($dir, $config)
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