<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  开发者中心控制器管理类controller_admin
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Extension\Tools\Develop\Generate;

use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class ControllerService extends BaseService
{

	public function __construct()
	{
		$this->tplType = ocConfig('TEMPLATE.file_type', 'html');
	}

	public function add(array $data = array())
	{
		$request = ocService()->request;
		$data  = $data ? : $request->getPost();
		$cname = explode(OC_DIR_SEP, trim($data['cname'], OC_DIR_SEP));
		
		if (count($cname) >= 2) {
			$this->mdlname = strtolower(ocGet(0, $cname));
			$this->cname   = strtolower(ocGet(1, $cname));
		} else {
			$this->mdlname = null;
			$this->cname   = strtolower(ocGet(0, $cname));
		}

		$this->vtype      = $data['vtype'];
		$this->ttype      = $data['ttype'];
		$this->createview = $request->getPost('createview');
		$this->controllerType = $request->getPost('controllerType');

        //加载配置
        $route = array(
            'module' => $this->mdlname,
            'controller' => $this->cname,
            'action' => null
        );

        $service = ocService();
        $service->config->loadModuleConfig($route);
        $service->config->loadControllerConfig($route);

        $CONF = ocService()->config->get();
		$this->tplType = ocGet('TEMPLATE.file_type', $CONF, 'html');
		
		$this->createController();
	}

    public function createAction()
    {
        $this->addAction('index');

        if ($this->vtype != 1) {
            $this->addAction('create');
            $this->addAction('update');
            $this->addAction('delete');
        }
    }

    public function addAction($actionName)
    {
        $data = array(
            'mdlname' => $this->mdlname,
            'actname' => $this->cname . '/' . $actionName,
            'createview' => 1,
            'ttype' => 'defaults'
        );

        $actionService = new ActionService();
        $actionService->add($data);
    }

	public function createController()
	{
		$mdlname = ucfirst($this->mdlname);
		$cname = ucfirst($this->cname);

		$className = $cname . 'Controller';
		$moduleClassName = $mdlname . 'Module';

		$modulePath = OC_APPLICATION_PATH . 'modules/' . $mdlname;
        $controlPath = $modulePath . "/controller";
        $moduleClassPath = $controlPath . "/{$moduleClassName}.php";

		if (empty($this->cname) || empty($this->ttype)) {
			$this->showError('控制器名称和模板类型为必填信息！');
		}

        if (!ocFileExists($moduleClassPath)) {
            $this->showError("模块文件“{$moduleClassName}.php”不存在或丢失。");
        }

        include_once($moduleClassPath);

		if ($this->mdlname) {
            $moduleNamespace = "app\\modules\\{$this->mdlname}\\controller";
            $moduleClass = $moduleNamespace . OC_NS_SEP . $moduleClassName;
            if (!ocClassExists($moduleClass)) {
                $this->showError("模块类不存在或丢失!");
            }
			foreach (self::$config['controller_actions'] as $controllerType => $controllerActions) {
				$providerClass = '\Ocara\Controllers\\Provider\\' . $controllerType;
				$reflection = new \ReflectionClass($moduleClass);
				if ($reflection->isSubclassOf($providerClass)) {
					$this->controllerType = $controllerType;
					break;
				}
			}
		} else {
            $moduleNamespace = 'app\controller';
            $moduleClass = 'app\controller\Module';
            if (!ocClassExists($moduleClass)) {
                $this->showError("模块类不存在或丢失!");
            }
        }

        $path = $controlPath . OC_DIR_SEP . $this->cname . "/{$className}.php";
		if (ocFileExists($path)) {
			$this->showError("模块或控制器文件已存在，如果需要覆盖，请先手动删除！");
		}

		$content  = "<?php\r\n";
		$content .= "namespace {$moduleNamespace}\\{$this->cname};\r\n";
        $content .= "\r\n";
		$content .= "use {$moduleNamespace}\\{$moduleClassName};\r\n";
		$content .= "\r\n";

		$content  .= "class {$className} extends {$moduleClassName}\r\n";
		$content  .= "{\r\n";
		$content  .= "\t/**\r\n";
		$content  .= "\t * 初始化控制器\r\n";
		$content  .= "\t */\r\n";
		$content  .= "\tprotected function _control()\r\n\t{}\r\n";
		$content  .= "}";

        ocCheckPath($controlPath . OC_DIR_SEP . $this->cname);
        ocCheckPath($controlPath . OC_DIR_SEP . $this->cname . '/actions');

        ocService()->file->createFile($path, 'wb');
        ocService()->file->writeFile($path, $content, true);

        $this->createAction();
	}
}
