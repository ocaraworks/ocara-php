<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  开发者中心控制器管理类controller_admin
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Develop;
use Ocara\Request;
use Ocara\Develop;
use Ocara\Config;
use Ocara\Service\File;

defined('OC_PATH') or exit('Forbidden!');

class action_admin
{

	public function __construct()
	{
		$this->tplType = ocConfig('TEMPLATE.file_type', 'html');
	}

	public function add(array $data = array())
	{
		$data    = $data ? : Request::getPost();
		$actname = explode(OC_DIR_SEP, trim($data['actname'], OC_DIR_SEP));
		
		$this->ttype      = $data['ttype'];
		$this->createview = Request::getPost('createview');
		$this->controllerType = 'Controller';

		if (empty($actname) || empty($this->ttype)) {
			Develop::error(Develop::back('控制器名称、动作名称和模板类型为必填信息！'));
		}
		
		$count = count($actname);
		$this->mdlname = false;
		
		if ($count >= 3) {
			$this->mdlname    = strtolower(ocGet(0, $actname));
			$this->cname      = strtolower(ocGet(1, $actname));
			$this->actionName = strtolower(ocGet(2, $actname));
		} elseif ($count == 2) {
			$this->cname      = strtolower(ocGet(0, $actname));
			$this->actionName = strtolower(ocGet(1, $actname));
		} else {
			Develop::error(Develop::back('缺少控制器！'));
		}
		
		$path = OC_ROOT . 'resource/conf/control';
	
		if ($this->mdlname) {
			if (is_dir($path = $path . OC_DIR_SEP . $this->mdlname)) {
				Config::loadControlConfig($path);
			}
		}

		if (is_dir($path = $path . OC_DIR_SEP . $this->cname)) {
			Config::loadControlConfig($path);
		}

		if (is_dir($path = $path . OC_DIR_SEP . $this->actionName)) {
			Config::loadControlConfig($path);
		}

		$CONF = Config::get();
		$this->tplType = ocGet('TEMPLATE.file_type', $CONF, 'html');
		$this->createAction();
	}
	
	public function createAction()
	{
		$mdlname = ucfirst($this->mdlname);
		$cname = ucfirst($this->cname);
		$actionName = ucfirst($this->actionName);
		$moduleClassName = $mdlname . 'Module';
		$controlClassName = $cname . 'Controller';
		$className = $actionName . 'Action';

		$appDir = 'application';
		
		if (!is_dir($controlPath = OC_ROOT . "{$appDir}/controller/{$mdlname}")) {
			Develop::error(Develop::back("{$this->mdlname}模块不存在.请先添加该模块。"));
		}
		
		if ($this->mdlname && !ocFileExists($controlPath . "/{$moduleClassName}.php")) {
			Develop::error(Develop::back("模块文件“{$moduleClassName}.php”不存在或丢失。"));
		}

		if (!is_dir($controlPath = $controlPath . "/{$cname}")) {
			Develop::error(Develop::back("{$this->cname}控制器不存在，请先添加该控制器。"));
		}

		$controllerClassPath = $controlPath . "/{$controlClassName}.php";
		if ($this->mdlname && !ocFileExists($controllerClassPath)) {
			Develop::error(Develop::back("控制器文件“{$controlClassName}.php”不存在或丢失。"));
		}

		$controlClass = $cname . 'Controller';
		$moduleNamespace = $mdlname ? "{$mdlname}\\" : '';
		$controlLongClass = 'Controller\\' . $moduleNamespace . $cname . '\\' . $controlClass;

		foreach (Develop::$config['controller_actions'] as $controllerType => $controllerActions) {
			$controllerNamespace = 'Ocara\Controller\\' . $controllerType;
			$reflection = new \ReflectionClass($controlLongClass);
			if ($reflection->isSubclassOf($controllerNamespace)) {
				$this->controllerType = $controllerType;
				break;
			}
		}

		if (!is_dir($controlPath = $controlPath . "/Action")) {
			@mkdir($controlPath);
		}
		
		$actionFile = $className . '.php';
		if (ocFileExists($controlPath . OC_DIR_SEP . $actionFile)) {
			Develop::error(Develop::back("动作文件已存在，如果需要覆盖，请先手动删除！"));
		}

		$content  = "<?php\r\n";
		$content .= "namespace Controller\\{$moduleNamespace}{$cname}\\Action;\r\n";
		$content .= "use Ocara\\Request;\r\n";
		$content .= "use Ocara\\Response;\r\n";
		$content .= "use Ocara\\Error;\r\n";
		$content .= "use {$controlLongClass};\r\n";
		
		$content .= "\r\n";
		$content .= "class {$className} extends {$controlClass}\r\n";

		$content .= "{\r\n";
		$content .= "\t/**\r\n";
		$content .= "\t * 初始化\r\n";
		$content .= "\t */\r\n";
		$content .= "\tprotected function _action()\r\n";
		$content .= "\t{}\r\n";

		$actions = Develop::$config['controller_actions'][$this->controllerType];
		if ($actions) {
			foreach ($actions as $actionName) {
				$actionDesc = Develop::$config['actions'][$actionName];
				$content .= "\r\n";
				$content .= "\t/**\r\n";
				$content .= "\t * {$actionDesc}\r\n";
				$content .= "\t */\r\n";
				$content .= "\tprotected function {$actionName}()\r\n";
				$content .= "\t{}\r\n";
			}
		}

		$content  .= "}";

		File::createFile($controlPath . OC_DIR_SEP . $actionFile , 'wb');
		File::writeFile($controlPath . OC_DIR_SEP . $actionFile, $content);
		
		$this->createview && $this->createView();
		
		die('添加成功！');
	}

	
	public function createView()
	{
		$path = OC_ROOT . "application/view/{$this->ttype}/template/";
		$path = $path . ($this->mdlname ? $this->mdlname . OC_DIR_SEP : false);
		$path = $path . "{$this->cname}";

		ocCheckPath($path);
		ocCheckPath(OC_ROOT . "application/view/{$this->ttype}/helper");
		ocCheckPath(OC_ROOT . "application/view/{$this->ttype}/part");
		ocCheckPath(OC_ROOT . "application/view/{$this->ttype}/layout");
		ocCheckPath(OC_ROOT . "public/css/{$this->ttype}");
		ocCheckPath(OC_ROOT . "public/images/{$this->ttype}");

		//新增css和images目录
		ocCheckPath(OC_ROOT . "public/css/{$this->ttype}/{$this->mdlname}/{$this->cname}");
		ocCheckPath(OC_ROOT . "public/images/{$this->ttype}/{$this->mdlname}/{$this->cname}");
		$this->addTpl($path, $this->actionName);
		
		return true;
	}

	public function addTpl($path, $tpl)
	{
		$path = ocDir($path) . $tpl . '.' . $this->tplType;
		$content = "Hello, I'm %s.{$this->tplType}.";
		
		File::openFile($path, 'wb');
		File::writeFile($path, sprintf($content, $tpl));
	}
}
