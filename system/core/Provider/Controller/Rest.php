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
        ocImport(OC_CORE . 'Validator.php');
        $class = ocConfig('VALIDATE_CLASS', 'Ocara\Service\Validate', true);
        $validator = new Validator(new $class);
        return $validator;
    }

    /**
     * 获取分页器
     */
    public function getPager()
    {
        ocImport(OC_SERVICE . 'library/Pager.php');
        $pager = new Pager();
        return $pager;
    }

    /**
     * 获取View视图类
     */
    public static function getView($route)
    {
        ocImport(OC_CORE . '/View/Rest.php');
        $view = new RestView();
        $view->setRoute($route);
        $view->init();
        return $view;
    }
}