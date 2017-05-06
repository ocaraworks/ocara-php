<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   权限控制插件Auth
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Service;
use Ocara\ServiceBase;

final class Auth extends ServiceBase
{
	private $_data = array();
	
	/**
	 * 设置权限数据
	 * @param array $data
	 */
	public function setData(array $data)
	{
		$this->_data = $data;
	}
	
	/**
	 * 新建角色
	 * @param string $roleList
	 */
	public function setRole($roleList)
	{
		ocSet($this->_data, $roleList, array());
	}

	/**
	 * 删除角色
	 * @param string $roleList
	 */
	public function delRole($roleList)
	{
		if (!is_array($roleList)) {
			$roleList = ocParseKey($roleList);
		} 
		
		ocDel($this->_data, ocParseKey($roleList));
	}

	/**
	 * 添加权限
	 * @param string $roleList
	 * @param string $authList
	 */
	public function setAuth($roleList, $authList)
	{
		if (!is_array($roleList)) {
			$roleList = ocParseKey($roleList);
		} 
		
		if (!is_array($authList)) {
			$authList = ocParseKey($authList);
		} 
		
		ocSet($this->_data, array_merge($roleList, $authList), null);
	}

	/**
	 * 删除权限
	 * @param string $roleList
	 * @param string $authList
	 */
	public function delAuth($roleList, $authList)
	{
		if (!is_array($roleList)) {
			$roleList = ocParseKey($roleList);
		} 
		
		if (!is_array($authList)) {
			$authList = ocParseKey($authList);
		} 
		
		ocDel($this->_data, array_merge($roleList, $authList));
	}

	/**
	 * 获取权限
	 * @param string $roleList
	 */
	public function getAuth($roleList = null)
	{
		return func_num_args() ? ocGet($roleList, $this->_data) : $this->_data;
	}

	/**
	 * 检测权限
	 * @param string $roleList
	 * @param string $authList
	 */
	public function check($roleList, $authList)
	{
		if (!is_array($roleList)) {
			$roleList = ocParseKey($roleList);
		} 
		
		if (!is_array($authList)) {
			$authList = ocParseKey($authList);
		} 

		$result = ocKeyExists(array_merge($roleList, $authList), $this->_data);
		return $result;
	}
}