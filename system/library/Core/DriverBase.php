<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 数据库驱动接口基类DriverBase
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Base;

defined('OC_PATH') or exit('Forbidden!');

class DriverBase extends Base
{
	protected $_instance;
	protected $_connection;
	protected $_stmt;
	protected $_prepared;
	protected $_pconnect;
	protected $_recordSet;
	protected $_errno;
	protected $_error;
	protected $_config;
	protected $_paramTypesMap = array();

	const DRIVE_TYPE_PDO = 'pdo';
    const DRIVE_TYPE_ODBC = 'odbc';
    const DRIVE_TYPE_DBA = 'dba';
    const DRIVE_TYPE_DBX = 'dbx';

	/**
	 * 是否长连接
	 * @param bool $pconnect
	 * @return bool
	 */
	public function is_pconnect($pconnect = true)
	{
		if ($this->_pconnect) {
			$this->_pconnect = $pconnect ? true : false;
		}
		return $this->_pconnect;
	}

	/**
	 * 是否预处理
	 * @param bool $prepare
	 * @return bool
	 */
	public function is_prepare($prepare = true)
	{
		if ($this->_prepared) {
			$this->_prepared = $prepare ? true : false;
		}
		return $this->_prepared;
	}

	/**
	 * 获取结果集数据
	 * @param $dataType
	 * @param bool $queryRow
	 * @return array
	 */
	public function get_all_result($dataType, $queryRow = false)
	{
		$result = array();

		if (is_object($this->_recordSet)) {
			if ($dataType == 'object') {
				while ($row = $this->fetch_assoc()) {
					$result[] = (object)$row;
					if ($queryRow) break;
				}
			} elseif ($dataType == 'array') {
				while ($row = $this->fetch_assoc()) {
					$result[] = $row;
					if ($queryRow) break;
				}
			} else {
                if (class_exists($dataType)) {
                    while ($row = $this->fetch_assoc()) {
                        $row = new $dataType($row);
                        $result[] = $row;
                        if ($queryRow) break;
                    }
                }
            }
		} else {
			$result = $this->_recordSet;
		}

		return $result;
	}

	/**
	 * 解析参数类型
	 */
	public function get_param_types()
	{
		return $this->_paramTypesMap;
	}
}