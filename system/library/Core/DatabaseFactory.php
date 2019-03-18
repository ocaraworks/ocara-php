<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 数据库接口类Database
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Base;

defined('OC_PATH') or exit('Forbidden!');

final class DatabaseFactory extends Base
{
    /**
     * 默认服务器名
     * @var string
     */
    protected static $_defaultServer = 'defaults';

    /**
     * 获取数据库实例
     * @param string $connectName
     * @param bool $master
     * @param bool $required
     * @return mixed|null
     * @throws \Ocara\Exceptions\Exception
     */
	public static function create($connectName = null, $master = true, $required = true)
	{
		if (empty($connectName)) {
			$connectName = self::$_defaultServer;
		}

		$database = self::_getDatabase($connectName, $master);
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
	    return self::$_defaultServer;
    }

    /**
     * 获取数据库对象
     * @param $connectName
     * @param bool $master
     * @return mixed|null
     * @throws \Ocara\Exceptions\Exception
     */
	private static function _getDatabase($connectName, $master = true)
	{
		$object = null;
		$config = self::getConfig($connectName);
		$index = $master ? 0 : 1;
		$hosts = ocForceArray(ocDel($config, 'host'));
		$connectName = $connectName . '_' . $index;

		if (isset($hosts[$index]) && $hosts[$index]) {
			$address = array_map('trim', explode(':', $hosts[$index]));
			$config['host']  = isset($address[0]) ? $address[0] : null;
			$config['port']  = isset($address[1]) ? $address[1] : null;
			$config['type']  = self::getDatabaseType($config);
			$config['class'] = $config['type'];
			$config['connect_name'] = $connectName;
			$object = self::_createDatabase('Databases', $config);
		}

		return $object;
	}

    /**
     * 获取数据库配置信息
     * @param null $connectName
     * @return array|mixed
     * @throws \Ocara\Exceptions\Exception
     */
	public static function getConfig($connectName = null)
	{
		if (empty($connectName)) {
			$connectName = self::$_defaultServer;
		}

        $config = ocForceArray(ocConfig(array('DATABASE', $connectName)));

		if ($callback = ocConfig(array('SOURCE', 'database', 'get_config'), null)) {
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
     * @throws \Ocara\Exceptions\Exception
     */
	public static function getDatabaseType(array $config)
	{
		$type = isset($config['type']) ? ucfirst($config['type']) : OC_EMPTY;
		$types = ocConfig('DATABASE_TYPE_MAP', array());
		return isset($types[$type]) ? $types[$type] : $type;
	}

    /**
     * 获取数据库对象
     * @param string $dir
     * @param array $config
     * @return mixed
     */
	private static function _createDatabase($dir, $config)
	{
		$class = $config['class'] . 'Database';
		$classFile = $dir . OC_DIR_SEP . $class . '.php';
		$classInfo = ServiceBase::classFileExists($classFile);

		if ($classInfo) {
			list($path, $namespace) = $classInfo;
			include_once($path);
			$class =  $namespace . 'Databases' . OC_NS_SEP . $class;
			if (class_exists($class, false)) {
				$object = new $class($config);
				return $object;
			}
		}

		ocService()->error->show('not_exists_database');
	}
}