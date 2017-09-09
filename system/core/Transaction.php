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

class Transaction extends Base
{
	/**
	 * 单例模式
	 */
	private static $_instance = null;
	private $_count = 0;
	private $_list = array();

	private function __clone(){}
	private function __construct(){}

	public static function getInstance()
	{
		if (self::$_instance === null) {
			self::$_instance = new static();
		}
		return self::$_instance;
	}

	/**
	 * 推入数据库
	 * @param ModelBase $database
	 */
	public function push($database)
	{
		if (self::hasBegan()) {
			$key = $database->getConnectName();
			if (!isset($this->_list[$key])) {
				$database->beginTransaction();
				$this->_list[$key] = $database;
			}
		}
	}

	/**
	 * 取消事务
	 * @param ModelBase $database
	 */
	public function cancel($database)
	{
		$key = $database->getConnectName();
		if (isset($this->_list[$key])) {
			ocDel($this->_list, $key);
		}
	}

	/**
	 * 是否已开始事务
	 * @return bool
	 */
	public function hasBegan()
	{
		return $this->_count > 0;
	}

	/**
	 * 事务开始
	 */
	public function begin()
	{
		$this->_count ++;
	}

	/**
	 * 事务提交
	 */
	public function commit()
	{
		if ($this->_count == 1) {
			self::_commitAll();
			$this->_count = 0;
			$this->_list = array();
		} elseif ($this->_count > 1) {
			$this->_count --;
		}
	}

	/**
	 * 事务回滚
	 */
	public function rollback()
	{
		if ($this->_count > 0) {
			$this->_count = 0;
			self::_rollbackAll();
			$this->_list = array();
		}
	}

	/**
	 * 提交所有事务
	 */
	protected function _commitAll()
	{
		foreach ($this->_list as $database) {
			$database->commit();
		}
	}

	/**
	 * 回滚所有事务
	 */
	protected function _rollbackAll()
	{
		foreach ($this->_list as $database) {
			$database->rollback();
		}
	}
}