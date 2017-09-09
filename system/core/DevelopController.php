<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  开发者中心控制器类DevelopController
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

class DevelopController extends Base
{
    /**
     * 析构函数
     */
    public function run()
    {
        $action = Ocara::getRoute('action');

        if (Develop::checkLogin() == false) {
            $this->loginAction();
        }

        if ($action && method_exists($this, $method = strtolower($action) . 'Action')) {
            $this->$method();
        } else {
            ocImport(OC_DEV_DIR . 'index.php');
        }
    }

    /**
     * 登出
     */
    public function logoutAction()
    {
        if (Develop::checkLogin()) {
            $caObj = Develop::loadClass('login.admin.class', 'login_admin');
            $caObj->logout();
        }

        header(
            "location:" . ocUrl(array(OC_DEV_SIGN, 'home', 'index'),
            array('action' => 'login'))
        );
    }

    /**
     * 登录
     */
    public function loginAction()
    {
        $this->runAction('login', 'login', 'global');
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
     * @param string $type
     * @param string $method
     * @param string $tpl
     * @param array $params
     */
    public static function runAction($type, $method = 'add', $tpl = 'module', array $params = array())
    {
        if ($type == 'login' && Develop::checkLogin()) {
            header("location:" . ocUrl(array(OC_DEV_DIR, 'home', 'index')));
        }

        if (Request::isPost()) {
            $action = Ocara::getRoute('action');
            if ($action != 'login' && $type == 'login') {
                header("location:" . ocUrl(array(OC_DEV_DIR, 'home', 'index'), array('action' => 'login')));
            }

            $caObj 	= Develop::loadClass($type . '.admin.class', $type . '_admin');
            call_user_func_array(array(&$caObj, $method), $params);

            if ($action != 'login' && $type == 'login') {
                header("location:" . ocUrl(array(OC_DEV_DIR, 'home', 'index')));
            }
            exit();
        }

        $tpl && Develop::tpl($type . '.admin', $tpl);
    }
}