<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  开发者中心用户管理类users_admin
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Extension\Tools\Develop\Generate;

use Ocara\Core\Develop;

class UsersService extends BaseService
{
	private $_username;
	private $_password;

	public function add()
	{
		$this->_username = $this->request->getPost('username');
		$this->_password = $this->request->getPost('password');
		$this->edit();
	}

	public function edit()
	{
		if (empty($this->_username) || empty($this->_password)) {
            $this->showError('请填满信息！');
		}
		
		$path = OC_DEV_DIR . 'data/users_data.php';

		if(ocFileExists($path)){
			include_once ($path);
		}

		$devUsers[$this->_username] = array(
			'password' => md5($this->_password)
		);

		$content = "<?php\r\n";

		foreach ($devUsers as $key => $value) {
			$content .= "\r\n";
			$content .= "\$devUsers['{$key}'] = array(\r\n";
			$content .= "\t'password' => '{$value['password']}'\r\n";
			$content .= ");";
		}

		$fileService = ocService()->file;
        $fileService->createFile($path, 'wb');
        $fileService->writeFile($path, $content);
	}
}

?>