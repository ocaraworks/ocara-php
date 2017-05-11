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
	 * 调试配置
	 */
	const DEBUG_NO = 0; //非调试
	const DEBUG_RETURN = 1; //返回调试内容
	const DEBUG_PRINT = 2; //用print_r()打印调试信息
	const DEBUG_DUMP = 3; //用var_dump()打印调试信息

	/**
	 * 工厂模式
	 */
	private static $_instances = array();

	private function __clone(){}
	private function __construct(){}

	/**
	 * 获取数据库实例
	 * @param string $server
	 * @param bool $master
	 * @param bool $required
	 */
	public static function getInstance($server = null, $master = true, $required = true)
	{
		if (empty($server)) {
			$server = 'default';
		}

		$database = self::_getDatabase($server, $master);
		if (is_object($database) && $database instanceof DatabaseBase) {
			return $database;
		}

		if ($required) {
			Error::show('not_exists_database', array($server));
		}

		return $database;
	}

	/**
	 * 获取数据库对象
	 * @param string $server
	 * @param bool $master
	 */
	private static function _getDatabase($server, $master = true)
	{
		$object = null;
		$config = self::getConfig($server);
		$index  = $master ? 0 : 1;
		$hosts   = ocForceArray(ocDel($config, 'host'));

		if (isset($hosts[$index]) && $hosts[$index]) {
			$address = array_map('trim', explode(':', $hosts[$index]));
			$config['host']  = isset($address[0]) ? $address[0] : null;
			$config['port']  = isset($address[1]) ? $address[1] : null;
			$config['type']  = self::getDatabaseType($config);
			$config['class'] = $config['type'];
			$object = self::_createDatabase('Database', $config['class'], $config);
		}

		return $object;
	}

	/**
	 * 获取数据库配置信息
	 * @param string $server
	 */
	public static function getConfig($server = null)
	{
		if (empty($server)) {
			$server = 'default';
		}

		$config = array();

		if ($callback = ocConfig('CALLBACK.database.get_config', null)) {
			$config = Call::run($callback, array($server));
		}

		if (empty($config)) {
			$config = ocForceArray(ocConfig("DATABASE.{$server}"));
		}

		return $config;
	}

	/**
	 * 获取数据库对象类名
	 * @param array $config
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
	 * 回滚所有的数据库连接的事务
	 */
	public static function rollback()
	{
		foreach (self::$_instances as $servers) {
			foreach ($servers as $database) {
				if ($database instanceof self && $database->isTrans()) {
					$database->transRollback();
				}
			}
		}
	}

	/**
	 * 获取数据库对象
	 * @param string $dir
	 * @param string $class
	 * @param array $config
	 */
	private static function _createDatabase($dir, $class, $config)
	{
		$class = $class . 'Database';
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