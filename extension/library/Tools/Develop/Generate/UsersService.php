<?php
/**
 * 开发者中心用户管理类users_admin
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Extension\Tools\Develop\Generate;

use Ocara\Core\Develop;

class UsersService extends BaseService
{
    private $_username;
    private $_password;

    public function add()
    {
        $this->_username = ocService()->request->getPost('username');
        $this->_password = ocService()->request->getPost('password');
        $this->edit();
    }

    public function edit()
    {
        if (empty($this->_username) || empty($this->_password)) {
            $this->showError('请填满信息！');
        }

        $path = ocPath('data', 'develop/users_data.php');

        if (ocFileExists($path)) {
            $devUsers = include_once($path);
        } else {
            $devUsers = array();
        }

        $devUsers[$this->_username] = array(
            'password' => md5($this->_password)
        );

        $fileCache = ocService()->fileCache;
        $fileCache->setData($devUsers, false, '开发者中心账号数据');
        $fileCache->format();
        $fileCache->save($path);
    }
}

?>