<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  开发者中心模块管理类module_admin
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Develop;

use Ocara\Ocara;
use Ocara\Develop;
use Ocara\Service\File;

class module_admin
{
	private $_mdlname;

	public function add()
	{
		$this->_mdlname = ocService()->request->getPost('mdlname');
		$this->createModel();
	}

	public function createModel()
	{
		$mdlname   = ucfirst($this->_mdlname);
		$className = $mdlname . 'Module';
		$baseController = ocService()->request->getPost('controllerType');

		$content = "<?php\r\n";
		$content .= "namespace Controller\\{$mdlname};\r\n";

		$content .= "use Ocara\\Request;\r\n";
		$content .= "use Ocara\\Response;\r\n";
		$content .= "use Ocara\\Error;\r\n";
		$content .= "use Controller\\{$baseController};\r\n";
		
		$content .= "\r\n";
		$content .= "class {$mdlname}Module extends {$baseController}\r\n";
		$content .= "{\r\n";
		$content .= "\t/**\r\n";
		$content .= "\t * 初始化模块\r\n";
		$content .= "\t */\r\n";
		$content .= "\tprotected function _module()\r\n\t{}\r\n";
		$content .= "}";
		
		if (!is_dir($modulePath = OC_APPLICATION_PATH . "controller/{$mdlname}")) {
			@mkdir($modulePath);
		}
		
		if (empty($this->_mdlname)) {
			Develop::error(Develop::back('模块名称为必填信息！'));
		}
		
		if (ocFileExists($path = $modulePath . "/{$className}.php")) {
			Develop::error(Develop::back('模块(Module)文件已存在，如果需要覆盖，请先手动删除！'));
		}

        ocService()->file->createFile($path, 'wb');
        ocService()->file->writeFile($path, $content);
		die("添加成功！");
	}
}

?>