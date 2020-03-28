<?php
/**
 * 开发者中心动作生成类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Extension\Tools\Develop\Generate;

use Ocara\Exceptions\Exception;

class ActionService extends BaseService
{
    public $tplType;
    public $mdltype;
    public $createview;
    public $controllerType;
    public $mdlname;
    public $cname;
    public $actionName;
    public $methodMaps;

    /**
     * ActionService constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->tplType = ocConfig('TEMPLATE.file_type', 'html');
    }

    /**
     * 添加
     * @param array $data
     * @throws Exception
     */
    public function add(array $data = array())
    {
        $request = ocService()->request;
        $data = $data ?: $request->getPost();
        $actname = explode(OC_DIR_SEP, trim($data['actname'], OC_DIR_SEP));

        $this->ttype = $data['ttype'];
        $this->mdltype = ocGet('mdltype', $data, '');
        $this->createview = ocGet('createview', $data, 0);
        $this->controllerType = 'Common';
        $this->methodMaps = !empty($data['method_maps']) ? $data['method_maps'] : array();

        if (empty($actname) || empty($this->ttype)) {
            $this->showError('控制器名称、动作名称和模板类型为必填信息！');
        }

        $count = count($actname);

        if ($count >= 3) {
            $this->mdlname = lcfirst(ocGet(0, $actname));
            $this->cname = lcfirst(ocGet(1, $actname));
            $this->actionName = lcfirst(ocGet(2, $actname));
        } elseif ($count == 2) {
            $this->mdlname = !empty($data['mdlname']) ? $data['mdlname'] : null;
            $this->cname = lcfirst(ocGet(0, $actname));
            $this->actionName = lcfirst(ocGet(1, $actname));
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
        $content = "Hello, I'm %s.{$this->tplType}.<br/>";
        $content .= "You can create more actions by clicking <a href=\"/pass/tools/index.php\">delelop center</a>.";
        ocService()->file->writeFile($path, sprintf($content, $file));
    }

    /**
     * 新建视图
     * @param $actionClass
     * @return bool
     */
    public function createView($actionClass)
    {
        $action = new $actionClass();
        $template = $this->ttype;

        if ($this->mdlname) {
            if ($this->mdltype) {
                $modulePath = ocPath($this->mdltype, $this->mdlname . '/view/');
            } else {
                $modulePath = ocPath('modules', $this->mdlname . '/view/');
            }
        } else {
            $modulePath = ocPath('view');
        }

        ocCheckPath($action->view->getModuleViewPath($this->mdlname, 'helper', $template, $modulePath));
        ocCheckPath($action->view->getModuleViewPath($this->mdlname, 'part', $template, $modulePath));
        ocCheckPath($action->view->getModuleViewPath($this->mdlname, 'layout', $template, $modulePath));
        ocCheckPath($action->view->getModuleViewPath($this->mdlname, 'template', $template, $modulePath));

        //检查css和images目录
        ocCheckPath(ocPath('css', ocDir($template, $this->mdlname, $this->cname)));
        ocCheckPath(ocPath('images', ocDir($template, $this->mdlname, $this->cname)));

        $path = $action->view->getModuleViewPath(
            $this->mdlname,
            ocDir('template', $this->cname),
            $template,
            $modulePath
        );

        $this->addTpl($path, $this->actionName);

        return true;
    }

    /**
     * 新建Action
     * @throws Exception
     */
    public function createAction()
    {
        $mdlname = ucfirst($this->mdlname);
        $cname = ucfirst($this->cname);
        $actionName = ucfirst($this->actionName);

        $moduleClassName = $mdlname . 'Module';
        $controlClassName = 'Controller';
        $className = $actionName . 'Action';

        $pathInfo = $this->getModuleRootPath($this->mdltype);
        $rootNamespace = $pathInfo['rootNamespace'];
        $rootModulePath = $pathInfo['rootModulePath'];

        if ($mdlname) {
            $moduleNamespace = $rootNamespace . "\\{$this->mdlname}\\controller";
            $modulePath = $rootModulePath . OC_DIR_SEP . $this->mdlname;
        } else {
            $moduleNamespace = $rootNamespace;
            $modulePath = $rootModulePath;
        }

        $controlPath = ocCommPath(ocDir($modulePath, 'controller', $this->cname));
        $controllerClassPath = ocCommPath($controlPath . $controlClassName . '.php');
        $controlNamespace = ocNamespace($moduleNamespace) . $this->cname;
        $controllerClass = $controlNamespace . OC_NS_SEP . $controlClassName;

        $actionNamespace = $controlNamespace;
        $actionFile = ocCommPath($controlPath . OC_DIR_SEP . $className . '.php');

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

        $this->controllerType = $controllerClass::controllerType();

        ocCheckPath($controlPath);

        if (ocFileExists($actionFile)) {
            $this->showError('动作文件已存在，如果需要覆盖，请先手动删除！');
        }

        $content = "<?php\r\n";
        $content .= "\r\n";
        $content .= "namespace {$actionNamespace};\r\n";
        $content .= "\r\n";
        //$content .= "use $controlNamespace\\{$controlClassName};\r\n";
        $content .= "class {$className} extends {$controlClassName}\r\n";

        $content .= "{\r\n";
        $content .= "    /**\r\n";
        $content .= "     * 初始化\r\n";
        $content .= "     */\r\n";
        $content .= "    protected function __action()\r\n";
        $content .= "    {\r\n";
        $content .= "    }\r\n";

        $actions = self::$config['controller_actions'][$this->controllerType];

        if (!empty($this->methodMaps[$this->actionName])) {
            $actions = $this->methodMaps[$this->actionName];
        }

        if ($actions) {
            foreach ($actions as $actionName) {
                $actionDesc = self::$config['actions'][$actionName];
                $content .= "\r\n";
                $content .= "    /**\r\n";
                $content .= "     * {$actionDesc}\r\n";
                $content .= "     */\r\n";
                $content .= "    public function {$actionName}()\r\n";
                $content .= "    {\r\n";
                $content .= "    }\r\n";
            }
        }

        $content .= "}";

        $fileService = ocService()->file;
        $fileService->createFile($actionFile, 'wb');
        $fileService->writeFile($actionFile, $content);

        if ($this->mdltype != 'console' && $this->controllerType == 'Common') {
            $this->createView($actionNamespace . OC_NS_SEP . $className);
        }
    }
}
