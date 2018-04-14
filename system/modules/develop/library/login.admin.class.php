<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  开发者中心用户登录登出类login_admin
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Develop;

use Ocara\Ocara;
use Ocara\Develop;

class login_admin
{

	/**
	 * 登录
	 */
	public function login()
	{
		$path = OC_DEV_DIR . 'data/users.data.php';
		
		if(!ocFileExists($path)){
            Ocara::services()->error->show('not_exists_file', array('users.data.php'));
		}

		$users = include(OC_DEV_DIR . 'data/users.data.php');
		$request = Ocara::services()->request;
		$username = $request->getPost('username');
		$password = $request->getPost('password');
		
		if(empty($username) || empty($password)){
			Develop::error(Develop::back('用户名或密码不能为空。'), 'global');
		}
		
		if (!array_key_exists($username, $users)) {
			Develop::error(Develop::back('用户名不存在。'), 'global');
		} elseif (!array_key_exists('password', $users[$username]) or $users[$username]['password'] != md5($password)) {
			Develop::error(Develop::back('密码错误。'), 'global');
		} else {
			$_SESSION['OC_DEV_LOGIN'] = true;
			$_SESSION['OC_DEV_USERNAME'] = $username;
			Ocara::services()->cookie->create(session_name(), session_id());
			header("location:" . ocUrl(array(OC_DEV_SIGN, 'home', 'index')));
		}
	}

	public function logout()
	{
		ocDel($_SESSION, 'OC_DEV_LOGIN', 'OC_DEV_LOGIN');
	}
}