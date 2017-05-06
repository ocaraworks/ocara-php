<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  开发者中心用户管理类users_admin
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Develop;
use Ocara\Request;
use Ocara\Develop;
use Ocara\Service\File;

class users_admin
{
	private $_username;
	private $_password;

	public function add()
	{
		$this->_username = Request::getPost('username');
		$this->_password = Request::getPost('password');
		$this->edit();
	}

	public function edit()
	{
		if (empty($this->_username) || empty($this->_password)) {
			Develop::error(Develop::back('请填满信息！'));
		}
		
		$path = OC_DEV_DIR . 'data/users.data.php';

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
	
		File::createFile($path, 'wb');
		File::writeFile($path, $content);
		die("操作成功！");
	}
}

?>