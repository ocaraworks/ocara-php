<?php
namespace Ocara\Service\Manager\Controller;
use Ocara\ServiceManager;
use Ocara\Validator;
use Ocara\View\Rest as RestView;

defined('OC_PATH') or exit('Forbidden!');

class Rest extends ServiceManager
{
    /**
     * 注册基本组件
     */
    public function register()
    {
        $this->_plugin
            ->bindSingleton('view', array($this, '_getView'), array($this->getRoute()))
            ->bindSingleton('validator', array($this, '_getValidator'))
            ->bindSingleton('db', function(){ Database::create('default'); })
            ->bindSingleton('pager', array($this, '_getPager'));
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
     * 获取View视图类
     */
    protected static function _getView($route)
    {
        ocImport(OC_CORE . '/View/Rest.php');
        $view = new RestView();
        $view->setRoute($route);
        $view->init();
        return $view;
    }
}