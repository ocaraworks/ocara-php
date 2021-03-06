<?php
/**
 * 开发者中心模块管理类module_admin
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Extension\Tools\Develop\Generate;

use Ocara\Core\ControllerBase;
use Ocara\Core\Develop;

class ModuleService extends BaseService
{
    protected $mdltype;
    protected $mdlname;
    protected $controllerType;

    public function add()
    {
        $this->mdltype = ocService()->request->getPost('mdltype');
        $this->mdlname = ocService()->request->getPost('mdlname');
        $this->controllerType = ocService()->request->getPost('controllerType');

        $this->createModule();
    }

    public function createModule()
    {
        $namespace = OC_EMPTY;
        $mdlname = ucfirst($this->mdlname);
        $className = $mdlname . 'Module';

        $controllerMaps = array(
            'console' => 'TaskController'
        );

        if (array_key_exists($this->mdltype, $controllerMaps)) {
            $baseController = $controllerMaps[$this->mdltype];
        } else {
            $baseController = ucfirst($this->controllerType) . 'Controller';
        }

        switch ($this->mdltype) {
            case 'modules':
                $namespace = 'app\\modules';
                $modulePath = ocPath('modules', "{$this->mdlname}");
                break;
            case 'console':
                $namespace = "app\\console";
                $modulePath = ocPath('console', "{$this->mdlname}");
                break;
            case 'tools':
                $namespace = "app\\tools";
                $modulePath = ocPath('tools', $this->mdlname);
                break;
            default:
                $this->showError('不支持的模块类型！');
        }

        $content = "<?php\r\n";
        $content .= "\r\n";
        $content .= "namespace {$namespace}\\{$this->mdlname}\\controller;\r\n";
        $content .= "\r\n";
        $content .= "use Base\\Controller\\{$baseController};\r\n";

        $content .= "\r\n";
        $content .= "class {$mdlname}Module extends {$baseController}\r\n";
        $content .= "{\r\n";
        $content .= "    /**\r\n";
        $content .= "     * 初始化模块\r\n";
        $content .= "     */\r\n";
        $content .= "    public function __module()\r\n";
        $content .= "    {\r\n";
        $content .= "    }\r\n";
        $content .= "}";

        $language = ocService()->app->getLanguage();

        ocCheckPath($modulePath . '/controller');
        ocCheckPath($modulePath . '/service');
        ocCheckPath($modulePath . '/config');
        ocCheckPath($modulePath . '/lang/' . $language);

        if ($this->mdltype != 'console' && $this->controllerType == 'Common') {
            ocCheckPath($modulePath . '/view/defaults/layout');
            ocCheckPath($modulePath . '/view/defaults/template');
            ocCheckPath($modulePath . '/view/defaults/helper');
            ocCheckPath($modulePath . '/view/defaults/part');
        }

        if (empty($this->mdlname)) {
            $this->showError('模块名称为必填信息！');
        }

        if (ocFileExists($path = $modulePath . "/controller/{$className}.php")) {
            $this->showError('模块(Module)文件已存在，如果需要覆盖，请先手动删除！');
        }

        $fileService = ocService()->file;
        $fileService->createFile($path, 'wb');
        $fileService->writeFile($path, $content);

        $this->createViewFile();
    }

    public function createViewFile()
    {
        if ($this->mdltype != 'console'
            && in_array($this->controllerType, array(ControllerBase::CONTROLLER_TYPE_COMMON))
        ) {
            $file = ocService()->file;
            $srcFile = OC_SYS . 'resource/application/files/application.view.defaults.layout.layout.ocara';
            $dstFile = ocFile($this->mdltype, ocDir($this->mdlname) . 'view/defaults/layout/layout.php');
            $headFile = ocFile($this->mdltype, ocDir($this->mdlname) . 'view/defaults/part/head.php');
            $file->copyFile($srcFile, $dstFile);
            $file->createFile($headFile);
        }
    }
}

?>