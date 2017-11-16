<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   普通控制器特性类Common
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Provider\Controller;

use Ocara\Ocara;
use Ocara\ServiceProvider;
use Ocara\View\Common as CommonView;
use Ocara\Service\Pager;
use Ocara\Service\Validator;
use Ocara\FormToken;
use Ocara\FormManager;

defined('OC_PATH') or exit('Forbidden!');

class Common extends ServiceProvider
{
    /**
     * 注册基本的组件
     */
    public function register()
    {
        $this->_container
            ->bindSingleton('view', array(&$this, 'getView'))
            ->bindSingleton('formToken', array(&$this, 'getFormToken'))
            ->bindSingleton('validator', array(&$this, 'getValidator'))
            ->bindSingleton('db', function(){Database::create('default');})
            ->bindSingleton('pager', array(&$this, 'getPager'))
            ->bindSingleton('formManager', array(&$this, 'getFormManager'));
    }

    /**
     * 获取验证器
     */
    public function getValidator()
    {
        $class = ocConfig('VALIDATE_CLASS', 'Ocara\Service\Validate', true);
        $validator = new Validator($class);
        return $validator;
    }

    /**
     * 获取分页器
     */
    public function getPager()
    {
        $pager = new Pager();
        return $pager;
    }

    /**
     * 获取表单令牌类
     */
    public function getFormToken()
    {
        $formToken = new FormToken();
        $formToken->setRoute($this->getRoute());
        return $formToken;
    }

    /**
     * 获取View视图类
     */
    public function getView()
    {
        $view = new CommonView();
        $view->setRoute($this->getRoute());
        $view->init();
        return $view;
    }

    /**
     * 获取View视图类
     */
    public function getFormManager()
    {
        $view = new FormManager();
        $view->setRoute($this->getRoute());
        return $view;
    }
}