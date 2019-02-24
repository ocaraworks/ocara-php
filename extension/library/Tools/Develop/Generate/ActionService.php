<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  开发者中心控制器管理类controller_admin
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Extension\Tools\Develop\Generate;

defined('OC_PATH') or exit('Forbidden!');

class ActionService extends BaseService
{

	public function __construct()
	{
		$this->tplType = ocConfig('TEMPLATE.file_type', 'html');
	}

    /**
     * 添加
     * @param array $data
     * @throws \Ocara\Exceptions\Exception
     */
	public function add(array $data = array())
	{
		$request = ocService()->request;
		$data    = $data ? : $request->getPost();
		$actname = explode(OC_DIR_SEP, trim($data['actname'], OC_DIR_SEP));

		$this->ttype      = $data['ttype'];
		$this->createview = $request->getPost('createview');
		$this->controllerType = 'Common';

		if (empty($actname) || empty($this->ttype)) {
			$this->showError('控制器名称、动作名称和模板类型为必填信息！');
		}

		$count = count($actname);

		if ($count >= 3) {
			$this->mdlname    = strtolower(ocGet(0, $actname));
			$this->cname      = strtolower(ocGet(1, $actname));
			$this->actionName = strtolower(ocGet(2, $actname));
		} elseif ($count == 2) {
		    $this->mdlname    = !empty($data['mdlname']) ? $data['mdlname'] : null;
			$this->cname      = strtolower(ocGet(0, $actname));
			$this->actionName = strtolower(ocGet(1, $actname));
		} else {
            $this->showError('缺少控制器！');
		}

		//加载配置
		$route = array(
		    'module' => $this->mdlname,
            'controller' => $this->cname,
            'action' => $this->actionName
        );

		$service = ocService();
        $service->config->loadModuleConfig($route);
        $service->config->loadControllerConfig($route);
        $service->config->loadActionConfig($route);

		$this->tplType = ocConfig('TEMPLATE.file_type', 'html');
		$this->createAction();
	}

    /**
     * 添加模板文件
     * @param $path
     * @param $file
     */
    public function addTpl($path, $file)
    {
        $path = ocDir($path) . $file . '.' . $this->tplType;
        $content = "Hello, I'm %s.{$this->tplType}.";
        ocService()->file->writeFile($path, sprintf($content, $file));
    }

    /**
     * 新建视图
     * @param $actionClass
     * @return bool
     * @throws \Ocara\Exceptions\Exception
     */
    public function createView($actionClass)
    {
        $action = new $actionClass();

        $template = $this->ttype;
        $modulePath = ocPath('modules', $this->mdlname .'/view/');

        ocCheckPath($action->view->getModuleViewPath($this->mdlname, 'helper', $template, $modulePath));
        ocCheckPath($action->view->getModuleViewPath($this->mdlname, 'part', $template, $modulePath));
        ocCheckPath($action->view->getModuleViewPath($this->mdlname, 'layout', $template, $modulePath));
        ocCheckPath($action->view->getModuleViewPath($this->mdlname, 'template', $template, $modulePath));

        //检查css和images目录
        ocCheckPath(ocPath('css', ocDir($template, $this->mdlname, $this->cname)));
        ocCheckPath(ocPath('images', ocDir($template, $this->mdlname, $this->cname)));

        $path = $action->view->getModuleViewPath(
            $this->mdlname,
            ocDir('template', $this->mdlname, $this->cname),
            $template,
            $modulePath
        );

        $this->addTpl($path, $this->actionName);

        return true;
    }

    /**
     * 新建Action
     * @throws \Ocara\Exceptions\Exception
     */
	public function createAction()
	{
		$mdlname = ucfirst($this->mdlname);
		$cname = ucfirst($this->cname);
		$actionName = ucfirst($this->actionName);

		$moduleClassName = $mdlname . 'Module';
        $controlClassName = $cname . 'Controller';
		$className = $actionName . 'Action';

		if ($mdlname) {
            $moduleNamespace = "app\\modules\\{$this->mdlname}\\controller";
            $modulePath = ocPath('application', 'modules/' . $this->mdlname);
        } else {
            $moduleNamespace = 'app\controller';
            $modulePath = ocDir(OC_APPLICATION_PATH);
        }

        $controlPath = ocCommPath(ocDir($modulePath, 'controller', $this->cname));
        $controllerClassPath = ocCommPath($controlPath . $controlClassName. '.php');
        $controlNamespace = ocNamespace($moduleNamespace, $this->cname);
        $controllerClass = $controlNamespace . $controlClassName;

        $actionNamespace = $controlNamespace . 'actions';
        $actionFile = ocCommPath($controlPath . 'actions/' . $className . '.php');

		if (!is_dir($modulePath)) {
            $this->showError("{$this->mdlname}模块目录不存在.请先添加该模块。");
		}

		if ($this->cname && !ocFileExists($modulePath . "/controller/{$moduleClassName}.php")) {
            $this->showError("模块文件“{$moduleClassName}.php”不存在或丢失。");
		}

		if (!is_dir($controlPath)) {
            $this->showError("{$this->cname}控制器目录不存在。");
		}

		if ($this->mdlname && !ocFileExists($controllerClassPath)) {
            $this->showError("控制器文件“{$controlClassName}.php”不存在或丢失。");
		}

		$this->controllerType = $controllerClass::providerType();

		ocCheckPath($controlPath . '/actions/');

		if (ocFileExists($actionFile)) {
            //$this->showError('动作文件已存在，如果需要覆盖，请先手动删除！');
		}

		$content  = "<?php\r\n";
		$content .= "namespace {$actionNamespace};\r\n";
        $content .= "\r\n";
		$content .= "use $controlNamespace{$controlClassName};\r\n";

		$content .= "\r\n";
		$content .= "class {$className} extends {$controlClassName}\r\n";

		$content .= "{\r\n";
		$content .= "\t/**\r\n";
		$content .= "\t * 初始化\r\n";
		$content .= "\t */\r\n";
		$content .= "\tprotected function _action()\r\n";
		$content .= "\t{}\r\n";

		$actions = self::$config['controller_actions'][$this->controllerType];
		if ($actions) {
			foreach ($actions as $actionName) {
				$actionDesc = self::$config['actions'][$actionName];
				$content .= "\r\n";
				$content .= "\t/**\r\n";
				$content .= "\t * {$actionDesc}\r\n";
				$content .= "\t */\r\n";
				$content .= "\tprotected function {$actionName}()\r\n";
				$content .= "\t{}\r\n";
			}
		}

		$content  .= "}";

		ocService()->file->createFile($actionFile , 'wb');
		ocService()->file->writeFile($actionFile, $content);

		$this->createview && $this->createView($actionNamespace . OC_NS_SEP . $className);
	}
}
