<?php
/**
 * 开发者中心模块
 */

namespace app\tools\dev\controller;

use \ReflectionException;
use Ocara\Controllers\Common;
use Ocara\Core\ExceptionHandler;
use Ocara\Exceptions\Exception;
use Ocara\Core\Event;

class DevModule extends Common
{
    /**
     * 初始化模块
     * @throws Exception
     * @throws ReflectionException
     */
    public function __module()
    {
        if (!ocConfig('OPEN_DEVELOP_CENTER', false)) {
            $this->error->show('not_open_module');
        }

        $this->checkVisitor();

        if (ocConfig('SYSTEM_RUN_MODE') != 'develop') {
            $this->error->show('invalid_run_mode');
        }

        defined('OC_MODULE_NAME') OR define('OC_MODULE_NAME', $this->getRoute('module'));

        $this->view
            ->setModuleRootViewPath(OC_EXT . 'resource/tools/develop/view/');

        $this->checkLogin();
    }

    /**
     * 注册事件
     * @throws Exception
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
     * @throws Exception
     * @throws ReflectionException
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
     * @param array $error
     * @param Event $event
     * @param object $eventTarget
     * @throws Exception
     * @throws ReflectionException
     */
    public function exceptionHandler($error, $event, $eventTarget)
    {
        if ($this->getRoute('action') != 'error') {
            $this->response->jump(
                'generate/error',
                array('content' => htmlspecialchars($error['message']))
            );
        }
    }

    /**
     * 校验访问者身份
     * @throws Exception
     */
    public function checkVisitor()
    {
        $limitServerIp = ocConfig('LIMIT.server_ips', array('127.0.0.1'));
        $limitServerDomain = ocConfig('LIMIT.server_domains', array('localhost'));
        $isValid = in_array($_SERVER['SERVER_ADDR'], $limitServerIp) || in_array($_SERVER['SERVER_ADDR'], $limitServerDomain);

        if (empty($isValid)) {
            $this->error->show('invalid_develop_system_ip');
        }
    }
}