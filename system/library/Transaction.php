<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   事务管理器Transaction
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

final class Transaction extends Base
{

	/**
	 * 单例模式
	 */
	private static $_instance = null;
	private static $_count = 0;
	private static $_list = array();

	private function __clone(){}
	private function __construct(){}

	public static function getInstance()
	{
		if (self::$_instance === null) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * 推入数据库
	 * @param ModelBase $database
	 */
	public static function push($database)
	{
		if (self::hasBegan()) {
			$key = $database->getConnectName();
			if (!isset(self::$_list[$key])) {
				$database->beginTransaction();
				self::$_list[$key] = $database;
			}
		}
	}

	/**
	 * 取消事务
	 * @param ModelBase $database
	 */
	public static function cancel($database)
	{
		$key = $database->getConnectName();
		if (isset(self::$_list[$key])) {
			ocDel(self::$_list, $key);
		}
	}

	/**
	 * 是否已开始事务
	 * @return bool
	 */
	public static function hasBegan()
	{
		return self::$_count > 0;
	}

	/**
	 * 事务开始
	 */
	public static function begin()
	{
		self::$_count ++;
	}

	/**
	 * 事务提交
	 */
	public static function commit()
	{
		if (self::$_count == 1) {
			self::_commitAll();
			self::$_count = 0;
		} elseif (self::$_count > 1) {
			self::$_count --;
		}
	}

	/**
	 * 事务回滚
	 */
	public static function rollback()
	{
		if (self::$_count > 0) {
			self::$_count = 0;
			self::_rollbackAll();
		}
	}

	/**
	 * 提交所有事务
	 */
	protected static function _commitAll()
	{
		foreach (self::$_list as $database) {
			$database->commit();
		}
	}

	/**
	 * 回滚所有事务
	 */
	protected static function _rollbackAll()
	{
		foreach (self::$_list as $database) {
			$database->rollback();
		}
	}
}