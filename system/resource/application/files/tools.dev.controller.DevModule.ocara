<?php
/**
 * 开发者中心模块
 */
namespace app\tools\dev\controller;

use Ocara\Controllers\Common;
use Ocara\Core\ExceptionHandler;

class DevModule extends Common
{
    /**
     * 初始化模块
     */
    public function __module()
    {
        if (ocConfig('SYSTEM_RUN_MODE') != 'develop') {
            $this->error->show('开发运行模式禁止使用该功能！');
        }

        defined('OC_MODULE_NAME') OR define('OC_MODULE_NAME', $this->getRoute('module'));

        $this->view
            ->setModuleRootViewPath(OC_EXT . 'resource/tools/develop/view/');

        $this->checkLogin();
    }

    /**
     * 注册事件
     */
    public function registerEvents()
    {
        parent::registerEvents();

        ocService()->exceptionHandler
            ->event(ExceptionHandler::EVENT_BEFORE_OUTPUT)
            ->append(array($this, 'exceptionHandler'));
    }

    /**
     * 检测登录
     */
    public function checkLogin()
    {
        $isLogin = $this->isLogin();
        $action = $this->getRoute('action');
        $this->view->assign('isLogin', $isLogin);

        if (!$isLogin && !in_array($action, array('login', 'logout', 'error'))) {
            return $this->response->jump('generate/login');
        }
    }

    /**
     * 是否登录
     * @return bool
     */
    public function isLogin()
    {
        return !empty($_SESSION['OC_DEV_LOGIN']);
    }

    /**
     * 异常处理
     * @param $exception
     * @param $event
     * @param $eventTarget
     */
    public function exceptionHandler($exception, $event, $eventTarget)
    {
        if ($this->getRoute('action') != 'error') {
            $this->response->jump(
                'generate/error',
                array('content' => htmlspecialchars($exception->getMessage()))
            );
        }
    }
}