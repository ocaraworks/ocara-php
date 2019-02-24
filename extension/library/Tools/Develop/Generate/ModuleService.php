<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  开发者中心模块管理类module_admin
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Extension\Tools\Develop\Generate;

use Ocara\Core\Develop;

class ModuleService extends BaseService
{
	protected $mdlname;

	public function add()
	{
		$this->mdlname = ocService()->request->getPost('mdlname');
		$this->createModule();
	}

	public function createModule()
	{
		$mdlname   = ucfirst($this->mdlname);
		$className = $mdlname . 'Module';
		$baseController = ocService()->request->getPost('controllerType') . 'Controller';

		$content = "<?php\r\n";
		$content .= "namespace app\\modules\\{$this->mdlname}\\controller;\r\n";
		$content .= "use Base\\{$baseController};\r\n";
		
		$content .= "\r\n";
		$content .= "class {$mdlname}Module extends {$baseController}\r\n";
		$content .= "{\r\n";
		$content .= "\t/**\r\n";
		$content .= "\t * 初始化模块\r\n";
		$content .= "\t */\r\n";
		$content .= "\tprotected function _module()\r\n\t{}\r\n";
		$content .= "}";

        $modulePath = OC_APPLICATION_PATH . "modules/{$this->mdlname}";
        $language = ocService()->app->getLanguage();

        ocCheckPath($modulePath . '/controller');
        ocCheckPath($modulePath . '/privates/config');
        ocCheckPath($modulePath . '/privates/lang/' . $language);
        ocCheckPath($modulePath . '/view/defautls/layout');
        ocCheckPath($modulePath . '/view/defautls/template');
        ocCheckPath($modulePath . '/view/defautls/helper');
        ocCheckPath($modulePath . '/view/defautls/part');

		if (empty($this->mdlname)) {
			$this->showError('模块名称为必填信息！');
		}
		
		if (ocFileExists($path = $modulePath . "/controller/{$className}.php")) {
            $this->showError('模块(Module)文件已存在，如果需要覆盖，请先手动删除！');
		}

        ocService()->file->createFile($path, 'wb');
        ocService()->file->writeFile($path, $content);

		die("添加成功！");
	}
}

?>