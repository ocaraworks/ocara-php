<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   普通控制器特性类Common
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Service\Manager\Controller;

use Ocara\FormToken;
use Ocara\ServiceManager;
use Ocara\View\Common as CommonView;

defined('OC_PATH') or exit('Forbidden!');

class Common extends ServiceManager
{
    /**
     * 注册基本的组件
     */
    public function register()
    {
        $this->_plugin
            ->bindSingleton('view', array($this, ' _getView'), array($this->getRoute()))
            ->bindSingleton('formToken', array($this, ' _getFormToken'))
            ->bindSingleton('validator', array($this, ' _getValidator'))
            ->bindSingleton('db', function(){ Database::create('default'); })
            ->bindSingleton('pager', array($this, ' _getPager'))
            ->bindSingleton('formManager', array($this, ' _getFormManager'), array($this->getRoute()));
    }

    /**
     * 获取验证器
     */
    protected function _getValidator()
    {
        ocImport(OC_CORE . 'Validator.php');
        $class = ocConfig('VALIDATE_CLASS', 'Ocara\Service\Validate', true);
        $validator = new Validator(new $class);
        return $validator;
    }

    /**
     * 获取分页器
     */
    protected function _getPager()
    {
        ocImport(OC_SERVICE . 'library/Pager.php');
        $pager = new Pager();
        return $pager;
    }

    /**
     * 获取表单令牌类
     */
    protected function _getFormToken()
    {
        ocImport(OC_CORE . 'FormToken.php');
        $formToken = new FormToken($this->getRoute());
        return $formToken;
    }

    /**
     * 获取View视图类
     */
    protected static function _getView($route)
    {
        ocImport(OC_CORE . '/View/Common.php');
        $view = new CommonView();
        $view->setRoute($route);
        $view->init();
        return $view;
    }

    /**
     * 获取View视图类
     */
    protected static function _getFormManager($route)
    {
        ocImport(OC_CORE . '/FormManager.php');
        $view = new FormManager();
        $view->setRoute($route);
        return $view;
    }
}