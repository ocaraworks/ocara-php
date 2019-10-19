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
	protected $instance;
	protected $connection;
	protected $stmt;
	protected $prepared;
	protected $pConnect;
	protected $recordSet;
	protected $errNo;
	protected $error;
	protected $config;
	protected $paramTypesMap = array();

	const DRIVE_TYPE_PDO = 'pdo';
    const DRIVE_TYPE_ODBC = 'odbc';
    const DRIVE_TYPE_DBA = 'dba';
    const DRIVE_TYPE_DBX = 'dbx';

    const DATA_TYPE_ARRAY = 1;
    const DATA_TYPE_OBJECT = 2;

    /**
     * 基本类型（除动态实体类外）
     * @return array
     */
    public static function base_diver_types()
    {
        return array(self::DATA_TYPE_ARRAY, self::DATA_TYPE_OBJECT);
    }

	/**
	 * 是否长连接
	 * @param bool $pConnect
	 * @return bool
	 */
	public function is_pconnect($pConnect = true)
	{
		if ($this->pConnect) {
			$this->pConnect = $pConnect ? true : false;
		}
		return $this->pConnect;
	}

	/**
	 * 是否预处理
	 * @param bool $prepare
	 * @return bool
	 */
	public function is_prepare($prepare = true)
	{
		if ($this->prepared) {
			$this->prepared = $prepare ? true : false;
		}
		return $this->prepared;
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

		if (is_object($this->recordSet)) {
			if ($dataType == self::DATA_TYPE_OBJECT) {
				while ($row = $this->fetch_assoc()) {
					$result[] = (object)$row;
					if ($queryRow) break;
				}
			} elseif ($dataType == self::DATA_TYPE_ARRAY) {
				while ($row = $this->fetch_assoc()) {
					$result[] = $row;
					if ($queryRow) break;
				}
			} else {
                if (class_exists($dataType)) {
                    while ($row = $this->fetch_assoc()) {
                        $result[] = $this->load_object($dataType, $row);
                        if ($queryRow) break;
                    }
                }
            }
		} else {
			$result = $this->recordSet;
		}

		return $result;
	}

    /**
     * 新建类对象
     * @param $class
     * @param $data
     * @return mixed
     */
	public function load_object($class, $data)
    {
        $object = new $class();

        if ($object instanceof BaseEntity) {
            $object->dataFrom($data);
        } else {
            foreach ($data as $key => $value) {
                $object->$key = $value;
            }
        }

        return $object;
    }

	/**
	 * 解析参数类型
	 */
	public function get_param_types()
	{
		return $this->paramTypesMap;
	}
}