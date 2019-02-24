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
			$this->showError('控制器名称、动作名称和模板类型为必填信息！');
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

		if ($mdlname) {
            $moduleNamespace = "app\\modules\\{$this->mdlname}\\controller";
            $modulePath = ocPath('application', 'modules/' . $this->mdlname);
        } else {
            $moduleNamespace = 'app\controller';
            $modulePath = ocDir(OC_APPLICATION_PATH);
        }

        $controlPath = ocDir($modulePath, 'controller', $this->cname);
        $controllerClassPath = $controlPath . $controlClassName. '.php';
        $controlNamespace = ocNamespace($moduleNamespace, $this->cname);
        $actionNamespace = $controlNamespace . 'actions';

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

		foreach (self::$config['controller_actions'] as $controllerType => $controllerActions) {
			$providerClass = 'Ocara\\Controllers\\Provider\\' . $controllerType;
			$reflection = new \ReflectionClass($controlNamespace . $controlClassName);
			if ($reflection->isSubclassOf($providerClass)) {
				$this->controllerType = $controllerType;
				break;
			}
		}

		ocCheckPath($controlPath . '/actions');

		if (ocFileExists($controlPath . $className . '.php')) {
            $this->showError('动作文件已存在，如果需要覆盖，请先手动删除！');
		}

		$content  = "<?php\r\n";
		$content .= "namespace {$actionNamespace};\r\n";
		$content .= "use $controlNamespace\\{$controlClassName};\r\n";

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

		$actionFile = $controlPath . $className . '.php';
		ocService()->file->createFile($actionFile , 'wb');
		ocService()->file->writeFile($actionFile, $content);

		$this->createview && $this->createView($actionNamespace . $className);

		die('添加成功！');
	}

    /**
     * 获取模块视图路径
     * @param null $subPath
     * @return mixed
     * @throws \Ocara\Exceptions\Exception
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
     * @param $actionClass
     * @return bool
     * @throws \Ocara\Exceptions\Exception
     */
	public function createView($actionClass)
	{
	    $action = new $actionClass();

        $template = $this->ttype;

        ocCheckPath($this->getModuleViewPath($this->mdlname, 'helper', $template));
        ocCheckPath($this->getModuleViewPath($this->mdlname, 'part', $template));
        ocCheckPath($this->getModuleViewPath($this->mdlname, 'layout', $template));
        ocCheckPath($this->getModuleViewPath($this->mdlname, 'template', $template));

        //检查css和images目录
		ocCheckPath(ocPath('css', ocDir($template, $this->mdlname, $this->cname)));
		ocCheckPath(ocPath('images', ocDir($template, $this->mdlname, $this->cname)));

        $path = $action->view->getModuleViewPath($this->mdlname, ocDir($this->mdlname, $this->cname), $template);
		$this->addTpl($path, $this->actionName);

		return true;
	}

    /**
     * 添加模板文件
     * @param $path
     * @param $file
     * @throws \Ocara\Exceptions\Exception
     */
	public function addTpl($path, $file)
	{
		$path = ocDir($path) . $file . '.' . $this->tplType;
		$content = "Hello, I'm %s.{$this->tplType}.";

		ocService()->file->openFile($path, 'wb');
        ocService()->file->writeFile($path, sprintf($content, $file));
	}
}
