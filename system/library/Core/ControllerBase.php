<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   应用控制器基类Controller
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\DatabaseFactory;
use Ocara\Core\ServiceProvider;
use Ocara\Interfaces\Controller as ControllerInterface;
use Ocara\Core\ModelBase;
use Ocara\Exceptions\Exception;
use Ocara\Controllers\Api;

defined('OC_PATH') or exit('Forbidden!');

class ControllerBase extends serviceProvider
{
	/**
	 * @var $_provider 控制器提供者
     * @var $isFormSubmit 是否POST提交
     * @var $checkForm 是否检测表单
	 */
    protected $route;
	protected $models;
    protected $isFormSubmit = false;
    protected $submitMethod = 'post';
    protected $checkForm = true;
    protected $hasRender = false;
    protected $contentType;
    protected $result;

    protected static $controllerType;

    const EVENT_AFTER_ACTION = 'afterAction';
    const EVENT_BEFORE_RENDER = 'beforeRender';
    const EVENT_AFTER_RENDER = 'afterRender';
    const EVENT_AFTER_CREATE_FORM = 'afterCreateForm';

	/**
	 * 初始化设置
	 */
	public function initialize()
	{
        $this->bindEvents($this);
        $this->session->boot();
        $this->formManager->setRoute($this->getRoute());
        $this->setPlugin($this->view);

        $this->view->assign('route', $this->getRoute());

		method_exists($this, '__start') && $this->__start();
		method_exists($this, '__module') && $this->__module();
		method_exists($this, '__control') && $this->__control();
	}

    /**
     * 注册基本组件
     */
    public function register()
    {
        $this->container->bindSingleton('db', function(){
            DatabaseFactory::create();
        });

        $services = array_merge(
            ocConfig(array('CONTROLLER_SERVICE_CLASS', 'All')),
            ocConfig(array('CONTROLLER_SERVICE_CLASS', self::controllerType()), array())
        );

        foreach ($services as $name => $class) {
            $this->container->bindSingleton($name, $class, array());
        }
    }

    /**
     * 注册事件
     */
    public function registerEvents()
    {
        $this->event(self::EVENT_AFTER_CREATE_FORM)
             ->append(array($this, 'afterCreateForm'));

        $this->event(self::EVENT_AFTER_ACTION)
             ->append(array($this, 'afterAction'));

        $this->event(self::EVENT_BEFORE_RENDER)
             ->append(array($this, 'beforeRender'));

        $this->event(self::EVENT_AFTER_RENDER)
             ->append(array($this, 'afterRender'));
    }

    /**
     * 获取提供器类
     * @param $controllerType
     * @return string
     * @throws Exception
     */
    public static function getControllerClass($controllerType)
    {
        $providers = ocConfig(array('ROUTE', 'providers'), array());

        if (isset($providers[$controllerType])) {
            $class = $providers[$controllerType];
        } else {
            $class = "\\Ocara\\Controllers\\{$controllerType}";
        }

        return $class;
    }

    /**
     * 获取提供器特性类
     * @param $controllerType
     * @return string
     * @throws Exception
     */
    public static function getFeatureClass($controllerType)
    {
        $features = ocConfig(array('ROUTE', 'features'), array());

        if (isset($features[$controllerType])) {
            $class = $features[$controllerType];
        } else {
            $class = "\\Ocara\\Controllers\\Feature\\{$controllerType}";
        }

        return $class;
    }

    /**
     * 获取提供者类型
     */
	public static function controllerType()
    {
	    return self::$controllerType ? ucfirst(self::$controllerType): 'Common';
    }

    /**
     * 设置路由
     * @param $route
     */
    public function setRoute($route)
    {
        $this->route = $route;
    }

    /**
     * 获取路由信息
     * @param string $name
     * @return array|null
     */
    public function getRoute($name = null)
    {
        if (isset($name)) {
            return isset($this->route[$name]) ? $this->route[$name] : null;
        }

        return $this->route;
    }

    /**
     * 是否已渲染
     * @return mixed
     */
    public function hasRender()
    {
        return $this->hasRender;
    }

    /**
     * 设置和获取表单提交方式
     * @param string $method
     * @return string
     */
    public function submitMethod($method = null)
    {
        if (isset($method)) {
            $method = $method == 'get' ? 'get' : 'post';
            $this->submitMethod = $method;
        }
        return $this->submitMethod;
    }

    /**
     * 设置和获取是否表单提交
     * @param bool $isFormSubmit
     * @return bool
     */
    public function isFormSubmit($isFormSubmit = null)
    {
        if (func_num_args()) {
            $this->isFormSubmit = $isFormSubmit ? true : false;
        } else {
            return $this->isFormSubmit;
        }
    }

    /**
     * 获取表单提交的数据
     * @param null $key
     * @param null $default
     * @return mixed
     */
    public function getSubmitData($key = null, $default = null)
    {
        $data = $this->submitMethod == 'post' ? $_POST : $_GET;
        $data = ocService()->request->getRequestValue($data, $key, $default);
        return $data;
    }

    /**
     * 获取表单并自动验证
     * @param null $name
     * @return mixed
     */
    public function form($name = null)
    {
        $model = null;
        if (!$name) {
            $name = $this->getRoute('controller');
            $model = $this->model();
        }

        $form = $this->formManager->getForm($name);
        if (!$form) {
            $form = $this->formManager->create($name);
            if ($model) {
                $form->model($model, false);
            }
            $this->fire(self::EVENT_AFTER_CREATE_FORM, array($name, $form));
        }

        return $form;
    }

    /**
     * 新建表单后处理
     * @param $name
     * @param $form
     * @param Event $event
     */
    public function afterCreateForm($name, $form, Event $event = null)
    {
        $this->view->assign($name, $form);
    }

    /**
     * 开启/关闭/检测表单令牌功能
     * @param null $check
     * @return bool
     */
    public function isCheckForm($check = null)
    {
        if ($check === null) {
            return $this->checkForm;
        }
        $this->checkForm = $check ? true : false;
    }

    /**
     * 表单检测
     */
    public function checkForm()
    {
        $this->isFormSubmit();
        if (!($this->isFormSubmit && $this->checkForm && $this->formManager->getForm()))
            return true;

        return $this->formManager->checkForm();
    }

    /**
     * 自动进行参数验证
     * @param array $data
     */
    public function validate(array $data = array())
    {
        $data = $data ? : $this->getSubmitData();
        $this->validator->validate($data);
    }

    /**
     * 获取或设置Model-静态属性保存
     * @param null $class
     * @return mixed
     */
    public function model($class = null)
    {
        if (empty($class)) {
            $class = '\app\dal\model\\'
                . DatabaseFactory::getDefaultServer()
                . OC_NS_SEP
                . ucfirst($this->getRoute('controller'));
        }

        if (isset($this->models[$class])) {
            $model = $this->models[$class];
            if (is_object($model) && $model instanceof ModelBase) {
                return $model;
            }
        }

        $this->models[$class] = new $class();
        return $this->models[$class];
    }

    /**
     * 获取不可访问的属性时
     * @param $key
     * @param $reason
     * @return mixed|null
     */
	public function _none($key, $reason)
	{
		if ($instance = $this->loadService($key)) {
			return $instance;
		}

        ocService()->error->show('no_property', array($key, $reason));
	}
}
