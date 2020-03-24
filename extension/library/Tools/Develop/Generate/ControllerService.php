<?php
/**
 * 开发者中心控制器生成类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Extension\Tools\Develop\Generate;

use Ocara\Core\ControllerBase;
use Ocara\Exceptions\Exception;


class ControllerService extends BaseService
{
    public function __construct()
    {
        $this->tplType = ocConfig('TEMPLATE.file_type', 'html');
    }

    public function add(array $data = array())
    {
        $request = ocService()->request;
        $data = $data ?: $request->getPost();
        $cname = explode(OC_DIR_SEP, trim($data['cname'], OC_DIR_SEP));

        if (count($cname) >= 2) {
            $this->mdlname = lcfirst(ocGet(0, $cname));
            $this->cname = lcfirst(ocGet(1, $cname));
        } else {
            $this->mdlname = null;
            $this->cname = lcfirst(ocGet(0, $cname));
        }

        $this->vtype = $data['vtype'];
        $this->ttype = $data['ttype'];
        $this->mdltype = $request->getPost('mdltype');
        $this->createview = $request->getPost('createview');
        $this->controllerType = 'Common';

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

        if ($this->mdltype != 'console' && $this->vtype != 1) {
            $this->addAction('create');
            $this->addAction('update');
            $this->addAction('delete');
            $this->addAction('read');
        }
    }

    public function addAction($actionName)
    {
        $data = array(
            'mdlname' => $this->mdlname,
            'actname' => $this->cname . '/' . $actionName,
            'controllerType' => $this->controllerType,
            'createview' => $this->controllerType == 'Common' ? 1 : 0,
            'ttype' => 'defaults',
            'mdltype' => $this->mdltype,
        );

        if ($this->controllerType == ControllerBase::CONTROLLER_TYPE_REST) {
            $data['method_maps'] = array(
                'create' => array('registerForms', 'submit'),
                'delete' => array('registerForms', 'submit'),
                'index' => array('registerForms', 'display'),
                'update' => array('registerForms', 'submit'),
                'read' => array('registerForms', 'display')
            );
        }

        $actionService = new ActionService();
        $actionService->add($data);
    }

    public function createController()
    {
        $mdlname = ucfirst($this->mdlname);
        $cname = ucfirst($this->cname);
        $className = 'Controller';
        $moduleClassName = $mdlname . 'Module';

        $pathInfo = $this->getModuleRootPath($this->mdltype);
        $rootNamespace = $pathInfo['rootNamespace'];
        $rootModulePath = $pathInfo['rootModulePath'];

        if ($this->mdlname) {
            $modulePath = ocDir($rootModulePath, $this->mdlname);
        } else {
            $modulePath = $rootModulePath;
        }

        $controlPath = $modulePath . "controller/";
        $moduleClassPath = $controlPath . "{$moduleClassName}.php";

        if (empty($this->cname) || empty($this->ttype)) {
            $this->showError('控制器名称和模板类型为必填信息！');
        }

        if (!ocFileExists($moduleClassPath)) {
            $this->showError("模块文件“{$moduleClassName}.php”不存在或丢失。");
        }

        include_once($moduleClassPath);

        if ($this->mdlname) {
            $moduleNamespace = $rootNamespace . "\\{$this->mdlname}\\controller";
            $moduleClass = $moduleNamespace . OC_NS_SEP . $moduleClassName;
            if (!ocClassExists($moduleClass)) {
                $this->showError("模块类不存在或丢失!");
            }
        } else {
            $moduleNamespace = $rootNamespace;
            $moduleClass = $rootNamespace . '\Module';
            if (!ocClassExists($moduleClass)) {
                $this->showError("模块类不存在或丢失!");
            }
        }

        $this->controllerType = $moduleClass::controllerType();

        if ($this->controllerType == ControllerBase::CONTROLLER_TYPE_REST) {
            $this->vtype = 2;
        }

        $path = $controlPath . $this->cname . "/{$className}.php";
        if (ocFileExists($path)) {
            $this->showError("控制器文件已存在，如果需要覆盖，请先手动删除！");
        }

        $content = "<?php\r\n";
        $content .= "namespace {$moduleNamespace}\\{$this->cname};\r\n";
        $content .= "\r\n";
        $content .= "use {$moduleNamespace}\\{$moduleClassName};\r\n";
        $content .= "\r\n";

        $content .= "class {$className} extends {$moduleClassName}\r\n";
        $content .= "{\r\n";
        $content .= "\t/**\r\n";
        $content .= "\t * 初始化控制器\r\n";
        $content .= "\t */\r\n";
        $content .= "\tpublic function __control()\r\n\t{}\r\n";
        $content .= "}";

        ocCheckPath($controlPath . $this->cname);

        $fileService = ocService()->file;
        $fileService->createFile($path, 'wb');
        $fileService->writeFile($path, $content, true);

        $this->createAction();
    }
}
