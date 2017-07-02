<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Session数据库方式处理类SessionDB
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Session;
use Ocara\Base;
use Ocara\Error;
use Ocara\Database;
use Ocara\DatabaseBase;
use \Exception;

defined('OC_PATH') or exit('Forbidden!');

class SessionDB extends Base
{
	protected $_plugin = null;

	protected $_database;
	protected $_table;
	protected $_fullname;
	protected $_fields;

	/**
	 * 析构函数
	 */
	public function __construct()
	{
		$server = ocConfig('SESSION.server', false);
		$database = Database::factory($server);

		if (empty($database)) {
			Error::show('not_exists_database');
		}

		$this->_plugin = $database;
		$table = explode('.', ocConfig('SESSION.location', 'ocsess'));
		$count = count($table);

		if ($count == 1) {
			$this->_database = 'default';
			$this->_table = reset($table);
		} elseif ($count == 2) {
			list($this->_database, $this->_table)  = $table;
			$this->_plugin->selectDatabase($this->_database);
		} else {
			Error::show('fault_session_table');
		}

		if (!$this->_plugin->tableExists($this->_table, false)) {
			$sql = $this->_plugin->getCreateSessionTableSql($this->_table);
			$ret = $this->_plugin->query($sql, false, false);
			$this->_plugin->checkError($ret, $sql);
		}

		$this->_fullname = $this->_plugin->getTableFullname($this->_table);
		$this->_fields   = $this->_plugin->getFields($this->_table);

		if (!(is_object($this->_plugin) && $this->_plugin instanceof DatabaseBase)) {
			Error::show('failed_db_connect');
		}
	}

	/**
	 * session打开
	 */
	public function open()
	{
		return is_object($this->_plugin) && $this->_plugin instanceof DatabaseBase;
	}

	/**
	 * session关闭
	 */
	public function close()
	{
		return true;
	}

	/**
	 * 读取session信息
	 * @param string $id
	 */
	public function read($id)
	{
		$condition = array('ocsess_id' => $id);
		$where[]   = $this->_plugin->parseCondition($condition);

		$condition = array('ocsess_expires' => date(ocConfig('DATE_FORMAT.datetime')));
		$where[]   = $this->_plugin->parseCondition($condition, 'AND', '>=');

		$where = array($this->_plugin->linkWhere($where));
		$where = $this->_plugin->getWhereSql($where);
		$sql   = $this->_plugin->getSelectSql('ocsess_data', $this->_fullname, $where);
		$data  = $this->_plugin->queryRow($sql);

		return $data ? stripslashes($data['ocsess_data']) : false;
	}

	/**
	 * 保存session
	 * @param string $id
	 * @param string $data
	 */
	public function write($id, $data)
	{

		$where = array('ocsess_id' => $id);
		$sql   = $this->_plugin->getSelectSql(
			'ocsess_data', $this->_fullname, $this->_plugin->parseCondition($where)
		);

		$datetimeFormat = ocConfig('DATE_FORMAT.datetime');
		$maxLifeTime = @ini_get('session.gc_maxlifetime');
		$curTime = date($datetimeFormat);
		$expires = date($datetimeFormat, strtotime("{$curTime}+{$maxLifeTime} second"));
		$result = $this->_plugin->queryRow($sql);

		if ($result) {
			$dbData  = array(
				'ocsess_expires' => $expires,
				'ocsess_data' 	 => stripslashes($data)
			);
			$this->_plugin->update($this->_table, $dbData, $where);
		} else {
			$dbData = array(
				'ocsess_id' 	 => $id,
				'ocsess_name' 	 => 'PHPSESSION',
				'ocsess_path' 	 => ocConfig('COOKIE.path', OC_EMPTY),
				'ocsess_domain'  => ocConfig('COOKIE.domain', false),
				'ocsess_expires' => $expires,
				'ocsess_data' 	 => stripslashes($data)
			);
			$this->_plugin->insert($this->_table, $dbData);
		}

		return $this->_plugin->errorExists();
	}

	/**
	 * 销毁session
	 * @param string $id
	 */
	public function destroy($id)
	{
		$condition = array('ocsess_id' => $id);
		$this->_plugin->delete($this->_table, $condition);
		return $this->_plugin->errorExists();
	}

	/**
	 * Session垃圾回收
	 * @param integer $saveTime
	 */
	public function gc($saveTime = false)
	{
		$curTime = date(ocConfig('DATE_FORMAT.datetime'));
		$condition = "ocsess_expires<'{$curTime}'";
		$this->_plugin->delete($this->_table, $condition);

		return $this->_plugin->errorExists();
	}
}
