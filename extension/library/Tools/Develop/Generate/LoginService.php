<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  开发者中心用户登录登出类login_admin
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Extension\Tools\Develop\Generate;

use Ocara\Core\Develop;

class LoginService extends BaseService
{

    /**
     * 登录
     */
    public function login()
    {
        $path = OC_EXT . 'resource/tools/develop/data/users_data.php';

        if (!ocFileExists($path)) {
            ocService()->error->show('not_exists_file', array('users_data.php'));
        }

        $users = include($path);

        $request = ocService()->request;
        $username = $request->getPost('username');
        $password = $request->getPost('password');

        if (empty($username) || empty($password)) {
            $this->showError('用户名或密码不能为空。');
        }

        if (!array_key_exists($username, $users)) {
            $this->showError('用户名不存在。');
        } elseif (!array_key_exists('password', $users[$username]) || $users[$username]['password'] != md5($password)) {
            $this->showError('密码错误。');
        } else {
            $_SESSION['OC_DEV_LOGIN'] = true;
            $_SESSION['OC_DEV_USERNAME'] = $username;
            ocService()->cookie->set(session_name(), session_id());
            header("location:" . ocUrl(array(OC_MODULE_NAME, 'generate', 'index')));
        }
    }

    public function logout()
    {
        ocDel($_SESSION, 'OC_DEV_LOGIN', 'OC_DEV_LOGIN');
    }
}