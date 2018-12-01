<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  开发者中心控制器管理类controller_admin
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Generators;

use Ocara\Core\Develop;

defined('OC_PATH') or exit('Forbidden!');

class ActionGenerator
{

	public function __construct()
	{
		$this->tplType = ocConfig('TEMPLATE.file_type', 'html');
	}

    /**
     * 添加
     * @param array $data
     * @throws \Ocara\Exceptions\Exception
     * @throws \ReflectionException
     */
	public function add(array $data = array())
	{
		$request = ocService()->request;
		$data    = $data ? : $request->getPost();
		$actname = explode(OC_DIR_SEP, trim($data['actname'], OC_DIR_SEP));
		
		$this->ttype      = $data['ttype'];
		$this->createview = $request->getPost('createview');
		$this->controllerType = 'Controller';

		if (empty($actname) || empty($this->ttype)) {
			Develop::error(Develop::back('控制器名称、动作名称和模板类型为必填信息！'));
		}
		
		$count = count($actname);
		$this->mdlname = null;
		
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
		
		$path = OC_ROOT . 'config/control';
	
		if ($this->mdlname) {
			if (is_dir($path = $path . OC_DIR_SEP . $this->mdlname)) {
				ocService()->config->load($path);
			}
		}

		if (is_dir($path = $path . OC_DIR_SEP . $this->cname)) {
			ocService()->config->load($path);
		}

		if (is_dir($path = $path . OC_DIR_SEP . $this->actionName)) {
			ocService()->config->load($path);
		}

		$CONF = ocService()->config->get();
		$this->tplType = ocGet('TEMPLATE.file_type', $CONF, 'html');
		$this->createAction();
	}

    /**
     * 新建Action
     * @throws \Ocara\Exceptions\Exception
     * @throws \ReflectionException
     */
	public function createAction()
	{
		$mdlname = ucfirst($this->mdlname);
		$cname = ucfirst($this->cname);
		$actionName = ucfirst($this->actionName);
		$moduleClassName = $mdlname . 'Module';
		$controlClassName = $cname . 'Controller';
		$className = $actionName . 'Action';
		
		if (!is_dir($controlPath = OC_APPLICATION_PATH . "{$appDir}/controller/{$mdlname}")) {
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
			$controllerNamespace = 'Ocara\Controllers\\Provider\\' . $controllerType;
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

		ocService()->file->createFile($controlPath . OC_DIR_SEP . $actionFile , 'wb');
		ocService()->file->writeFile($controlPath . OC_DIR_SEP . $actionFile, $content);
		
		$this->createview && $this->createView();
		
		die('添加成功！');
	}

    /**
     * 获取模块视图路径
     * @param null $subPath
     * @return bool|mixed|string
     */
    public function getViewPath($subPath = null)
    {
        $path = $this->mdlname
            . '/view/'
            . $this->ttype
            . OC_DIR_SEP
            . $subPath;
        return ocPath('modules', $path);
    }

    /**
     * 新建视图
     * @return bool
     * @throws \Ocara\Exceptions\Exception
     */
	public function createView()
	{
		$path = $this->getViewPath('template');
		$path = $path . ($this->mdlname ? $this->mdlname . OC_DIR_SEP : false);
		$path = $path . "{$this->cname}";
        $ttypePath = ocDir($this->ttype);
        $cssPath = ocPath('css', $ttypePath);
        $imagesPath = ocPath('images', $ttypePath);

		ocCheckPath($path);
        ocCheckPath($this->getViewPath('helper'));
        ocCheckPath($this->getViewPath('part'));
        ocCheckPath($this->getViewPath('layout'));

		ocCheckPath($cssPath);
		ocCheckPath($imagesPath);

		//新增css和images目录
        ocCheckPath(ocDir($cssPath) . "{$this->mdlname}/{$this->cname}");
        ocCheckPath(ocDir($imagesPath) . "{$this->mdlname}/{$this->cname}");

		$this->addTpl($path, $this->actionName);
		
		return true;
	}

    /**
     * 添加模板文件
     * @param $path
     * @param $tpl
     */
	public function addTpl($path, $tpl)
	{
		$path = ocDir($path) . $tpl . '.' . $this->tplType;
		$content = "Hello, I'm %s.{$this->tplType}.";
		
		ocService()->file->openFile($path, 'wb');
        ocService()->file->writeFile($path, sprintf($content, $tpl));
	}
}
