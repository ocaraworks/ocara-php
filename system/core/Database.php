<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 数据库接口类Database
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

final class Database extends Base
{
	/**
	 * 工厂模式
	 */
	private function __clone(){}
	private function __construct(){}

	/**
	 * 获取数据库实例
	 * @param string $connectName
	 * @param bool $master
	 * @param bool $required
	 * @return null
	 * @throws Exception\Exception
	 */
	public static function create($connectName = null, $master = true, $required = true)
	{
		if (empty($connectName)) {
			$connectName = 'main';
		}

		$database = self::_getDatabase($connectName, $master);
		if (is_object($database) && $database instanceof DatabaseBase) {
			return $database;
		}

		if ($required) {
			Error::show('not_exists_database', array($connectName));
		}

		return $database;
	}

	/**
	 * 获取数据库对象
	 * @param string $connectName
	 * @param bool $master
	 * @return null
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
			$object = self::_createDatabase('Database', $config);
		}

		return $object;
	}

	/**
	 * 获取数据库配置信息
	 * @param string $connectName
	 * @return array|bool|mixed|null
	 */
	public static function getConfig($connectName = null)
	{
		if (empty($connectName)) {
			$connectName = 'main';
		}

		$config = array();

		if ($callback = ocConfig('CALLBACK.database.get_config', null)) {
			$config = Call::run($callback, array($connectName));
		}

		if (empty($config)) {
			$config = ocForceArray(ocConfig("DATABASE.{$connectName}"));
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

		if ($type == 'Mysql') {
			$type = 'Mysqli';
		}

		return $type;
	}

	/**
	 * 获取数据库对象
	 * @param string $dir
	 * @param array $config
	 */
	private static function _createDatabase($dir, $config)
	{
		$class = $config['class'] . 'Database';
		$classFile = $dir . OC_DIR_SEP . $class . '.php';
		$classInfo = ServiceBase::classFileExists($classFile);

		if ($classInfo) {
			list($path, $namespace) = $classInfo;
			include_once($path);
			$class =  $namespace . 'Database' . OC_NS_SEP . $class;
			if (class_exists($class, false)) {
				$object = new $class($config);
				return $object;
			}
		}

		Error::show('not_exists_database');
	}
}