<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 缓存接口类Cache - 工厂类
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

final class Cache extends Base
{
	/**
	 * 工厂模式
	 */
	private function __clone(){}
	private function __construct(){}

	/**
	 * 新建缓存实例
	 * @param string $server
	 * @param bool $required
	 */
	public static function create($server = 'default', $required = true)
	{
		if (empty($server)) {
			$server = 'default';
		}

		$object = self::_connect($server, $required);
		if (is_object($object) && $object instanceof CacheBase) {
			return $object;
		}

		return Error::check(
			'not_exists_cache', array($server), $required
		);
	}

	/**
	 * 获取配置信息
	 * @param string $server
	 */
	public static function getConfig($server = null)
	{
		if (empty($server)) {
			$server = 'default';
		}

		$config = array();

		if ($callback = ocConfig('CALLBACK.cache.get_config', null)) {
			$config = Call::run($callback, array($server));
		}

		if (empty($config)) {
			$config = ocForceArray(ocConfig("CACHE.{$server}"));
		}

		return $config;
	}
	
	/**
	 * 连接缓存
	 * @param string $server
	 * @param bool $required
	 */
	private static function _connect($server, $required = true)
	{
		$config = self::getConfig($server);
		$type 	= ucfirst(ocConfig('CACHE.' . $server . '.type'));

		$classInfo = ServiceBase::classFileExists("Cache/{$type}.php");
		if ($classInfo) {
			list($path, $namespace) = $classInfo;
			include_once($path);
			$class  = $namespace . 'Cache' . OC_NS_SEP . $type;
			if (class_exists($class, false)) {
				$config['connect_name'] = $server;
				$object = new $class($config, $required);
				return $object;
			}
		}

		Error::show('not_exists_cache');
	}
}