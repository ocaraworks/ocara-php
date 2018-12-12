<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  开发者中心控制器类DevelopController
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Develop\Controller\Generate;

use Ocara\Develop\Controller\Module;
use Ocara\Develop\Services\Generate\LoginService;
use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class GenerateController extends Module
{
    /**
     * 析构函数
     */
    public function _control()
    {
        $this->checkLogin();
    }

    /**
     * 检测登录
     */
    public function checkLogin()
    {
        if (empty($_SESSION['OC_DEV_LOGIN']) && $this->getRoute('action') == 'login') {
            $this->reponse->redirect('generate/login');
        }
    }

    /**
     * 登出
     */
    public function logoutAction()
    {
        if ($this->checkLogin()) {
            $service = (new LoginService());
            $service->logout();
        }

        $this->reponse->redirect('generate/index');
    }

    /**
     * 登录
     */
    public function loginAction()
    {
        $this->runAction('login', 'global');
    }

    /**
     * 首页
     * @throws \Ocara\Exceptions\Exception
     */
    public function indexAction()
    {
        $this->runAction('index');
    }

    /**
     * 用户管理
     */
    public function adminUsersAction()
    {
        $this->runAction('users');
    }

    /**
     * 动作管理
     */
    public function adminActionAction()
    {
        $this->runAction('action');
    }

    /**
     * 控制器管理
     */
    public function adminControlAction()
    {
        $this->runAction('controller');
    }

    /**
     * 模型管理
     */
    public function adminModelAction()
    {
        $this->runAction('model');
    }

    /**
     * 字段更新
     */
    public function adminFieldsAction()
    {
        $this->runAction('fields');
    }

    /**
     * 模块管理
     */
    public function adminModuleAction()
    {
        $this->runAction('module');
    }

    /**
     * 应用管理
     */
    public function adminAppAction()
    {
        $this->runAction('app');
    }

    /**
     * 后台管理
     */
    public function adminBackAction()
    {
        $this->runAction('back');
    }

    /**
     * 运行action
     * @param $serviceName
     * @param string $method
     * @param string $tpl
     * @param array $params
     * @throws Exception
     */
    public function runAction($serviceName, $method = 'add', $tpl = 'module', array $params = array())
    {
        if (!ocService()->request->isPost()){
            return $this->tpl($method, $tpl);
        }

        try {
            $serviceClass = sprintf('\Ocara\Develop\Services\Generate\%sService', ucfirst($serviceName));
            $service = new $serviceClass();
            call_user_func_array(array(&$service, $method), $params);
        } catch (Exception $exception) {
            $this->tpl('error', $tpl, get_defined_vars());
        }
    }

    /**
     * 输出模板
     * @param $filename
     * @param $tpl
     * @param array $vars
     * @throws \Ocara\Exceptions\Exception
     */
    public function tpl($filename, $tpl, array $vars = array())
    {
        (is_array($vars) && $vars) && extract($vars);

        if($tpl == 'global'){
            $path = OC_DEV_DIR . 'view/layout/global.php';
        } else {
            $path = OC_DEV_DIR . ($filename ? 'view/template/generate/' . $filename : 'index') . '.php';
        }

        if (!ocFileExists($path)) {
            throw new Exception($filename . '模板文件不存在.');
        }

        if($tpl == 'global'){
            $contentFile = $filename;
            include($path);
        } else {
            ocImport(OC_DEV_DIR . 'view/layout/header.php');
            include($path);
            ocImport(OC_DEV_DIR . 'view/layout/footer.php');
        }
    }

    /**
     * 错误返回
     * @param $msg
     * @return string
     * @throws \Ocara\Exceptions\Exception
     */
    public function back($msg)
    {
        $back = ocService()->html->createElement('a', array(
            'href' => 'javascript:;',
            'onclick' => 'setTimeout(function(){history.back();},0)',
        ), '返回');

        return  $msg . $back;
    }
}