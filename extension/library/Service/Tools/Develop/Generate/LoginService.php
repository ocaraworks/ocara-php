<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  开发者中心用户登录登出类login_admin
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Extension\Service\Tools\Develop\Generate;

use Ocara\Core\Develop;

class LoginService extends BaseService
{

	/**
	 * 登录
	 */
	public function login()
	{
		$path = OC_DEV_DIR . 'data/users.data.php';
		
		if(!ocFileExists($path)){
            ocService()->error->show('not_exists_file', array('users.data.php'));
		}

		$users = include(OC_DEV_DIR . 'data/users.data.php');
		$request = ocService()->request;
		$username = $request->getPost('username');
		$password = $request->getPost('password');
		
		if(empty($username) || empty($password)){
            $this->showError('用户名或密码不能为空。');
		}
		
		if (!array_key_exists($username, $users)) {
            $this->showError('用户名不存在。');
		} elseif (!array_key_exists('password', $users[$username]) || $users[$username]['password'] != md5($password)) {
            $this->showError('密码错误。');
		} else {
			$_SESSION['OC_DEV_LOGIN'] = true;
			$_SESSION['OC_DEV_USERNAME'] = $username;
			ocService()->cookie->create(session_name(), session_id());
			header("location:" . ocUrl(array(OC_MODULE_NAME, 'home', 'index')));
		}
	}

	public function logout()
	{
		ocDel($_SESSION, 'OC_DEV_LOGIN', 'OC_DEV_LOGIN');
	}
}