<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   数据库类接口Database
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Interfaces;

defined('OC_PATH') or exit('Forbidden!');

/**
 * 数据库对象接口
 * @author Administrator
 */
interface Database
{
	/**
	 * 获取PDO参数
	 * @param array $config
	 */
	public function getPdoParams($config);
	
	/**
	 * 获取以表字段名为键值的数组
	 * @param string $table
	 */
	public function getFields($table);

	/**
	 * 设置数据库编码
	 * @param $charset
	 */
	public function setCharset($charset);

	/**
	 * 选择数据库
	 * @param $name
	 */
	public function selectDb($name);

	/**
	 * 通过字段类型转换数据类型
	 * @param $fields
	 * @param $data
	 */
	public function formatFieldValues($fields, $data = array());
}