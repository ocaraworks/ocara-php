<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   权限控制插件Auth
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Service;

use Ocara\Core\ServiceBase;

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
	 * 获取角色信息
	 * @param string $roleList
	 */
	public function getRole($roleList)
	{
		return ocGet($roleList, $this->_data);
	}

	/**
	 * 删除角色
	 * @param string|array $roleList
	 */
	public function delRole($roleList)
	{
		if (!is_array($roleList)) {
			$roleList = ocParseKey($roleList);
		} 
		
		ocDel($this->_data, $roleList);
	}

	/**
	 * 添加权限
	 * @param string|array $roleList
	 * @param string|array $routeList
	 * @param integer $allowed
	 */
	public function setAuth($roleList, $routeList, $allowed = true)
	{
		if (!is_array($roleList)) {
			$roleList = ocParseKey($roleList);
		} 
		
		if (!is_array($routeList)) {
			$routeList = ocParseKey($routeList);
		}

		$allowed = $allowed === true ? 1 : 0;
		ocSet($this->_data, array_merge($roleList, $routeList), $allowed);
	}

	/**
	 * 删除权限
	 * @param string|array $roleList
	 * @param string|array $routeList
	 */
	public function delAuth($roleList, $routeList)
	{
		if (!is_array($roleList)) {
			$roleList = ocParseKey($roleList);
		}

		if (!is_array($routeList)) {
			$routeList = ocParseKey($routeList);
		}

		ocDel($this->_data, array_merge($roleList, $routeList));
	}

	/**
	 * 获取权限
	 * @param string|array $roleList
	 * @param string|array $routeList
	 */
	public function getAuth($roleList = null, $routeList = null)
	{
		if (!isset($roleList)) {
			return $this->_data;
		}

		if (!is_array($roleList)) {
			$roleList = ocParseKey($roleList);
		}

		if ($routeList && !is_array($routeList)) {
			$roleList = ocParseKey($routeList);
		}

		$key = $routeList ? array_merge($roleList, $routeList) : $roleList;

		return ocGet($key, $this->_data);
	}

	/**
	 * 检测权限
	 * @param string $roleList
	 * @param string $routeList
	 */
	public function check($roleList, $routeList)
	{
		if (!is_array($roleList)) {
			$roleList = ocParseKey($roleList);
		} 
		
		if (!is_array($routeList)) {
			$routeList = ocParseKey($routeList);
		} 

		$result = ocGet(array_merge($roleList, $routeList), $this->_data);
		return $result == 1 ? true : false;
	}
}