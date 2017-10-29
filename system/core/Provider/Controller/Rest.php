<?php
namespace Ocara\Provider\Controller;
use Ocara\ServiceProvider;
use Ocara\Validator;
use Ocara\View\Rest as RestView;

defined('OC_PATH') or exit('Forbidden!');

class Rest extends ServiceProvider
{
    /**
     * 注册基本组件
     */
    public function register()
    {
        $this->_container
            ->bindSingleton('view', array(&$this, 'getView'), array($this->getRoute()))
            ->bindSingleton('validator', array(&$this, 'getValidator'))
            ->bindSingleton('db', function(){Database::create('default');})
            ->bindSingleton('pager', array(&$this, 'getPager'));
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
     * 获取View视图类
     */
    public static function getView($route)
    {
        $view = new RestView();
        $view->setRoute($route);
        $view->init();
        return $view;
    }
}