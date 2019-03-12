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
use Ocara\Core\Route;

defined('OC_PATH') or exit('Forbidden!');

class BaseController extends serviceProvider implements ControllerInterface
{
	/**
	 * @var $_provider 控制器提供者
     * @var $_isFormSubmit 是否POST提交
     * @var $_checkForm 是否检测表单
	 */
    protected $_route;
	protected $_models;
    protected $_isFormSubmit = null;
    protected $_submitMethod = 'post';
    protected $_checkForm = true;
    protected $_hasRender = false;
    protected $_isApi = false;
    protected $_contentType;
    protected $_result;

    protected static $_controllerType;

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
        $this->_plugin = $this->view;

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
        $this->_container->bindSingleton('db', function(){
            DatabaseFactory::create();
        });

        $services = ocConfig('CONTROLLER_SERVICE_CLASS.Common');
        foreach ($services as $name => $class) {
            $this->_container->bindSingleton($name, $class, array());
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
     * 是否API
     * @param bool $isApi
     * @return bool
     */
    public function isApi($isApi = true)
    {
        if (func_get_args()) {
            $this->_isApi = $isApi ? true : false;
        }

        return $this->_isApi;
    }

    /**
     * 获取提供器类
     * @param $controllerType
     * @return string
     * @throws \Ocara\Exceptions\Exception
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
     * @throws \Ocara\Exceptions\Exception
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
	    return self::$_controllerType ? ucfirst(self::$_controllerType): 'Common';
    }

    /**
     * 设置路由
     * @param $route
     */
    public function setRoute($route)
    {
        $this->_route = $route;
    }

    /**
     * 获取路由信息
     * @param string $name
     * @return array|null
     */
    public function getRoute($name = null)
    {
        if (isset($name)) {
            return isset($this->_route[$name]) ? $this->_route[$name] : null;
        }

        return $this->_route;
    }

    /**
     * 执行动作
     * @param string $actionMethod
     */
	public function doAction($actionMethod)
    {
        if (!$this->isFormSubmit()) {
            if (method_exists($this, 'isSubmit')) {
                $this->isFormSubmit($this->isSubmit());
            } elseif ($this->submitMethod() == 'post') {
                $this->isFormSubmit($this->request->isPost());
            }
        }

        if ($actionMethod == '__action') {
            $this->_doClassAction();
        } else {
            $this->$actionMethod();
            $this->render();
        }

        $this->fire(self::EVENT_AFTER_ACTION);
	}

    /**
     * 执行动作类实例
     */
	protected function _doClassAction()
    {
        if (method_exists($this, '__action')) {
            $this->__action();
        }

        if (method_exists($this, 'registerForms')) {
            $this->registerForms();
        }

        $this->checkForm();

        if ($this->request->isAjax()) {
            if (method_exists($this, 'ajax')) {
                $this->ajax();
            }
            $this->render(false);
        } elseif ($this->isFormSubmit() && method_exists($this, 'submit')) {
            $this->submit();
            $this->formManager->clearToken();
            $this->render(false);
        } else {
            if (method_exists($this, 'display')) {
                $this->display();
            }
            $this->render();
        }
    }

    /**
     * 后置处理
     */
    public function afterAction()
    {}

    /**
     * 渲染视图
     * @param bool $userDefault
     */
	public function render($userDefault = true)
    {
        if ($this->hasRender()) return;

        if ($this->isApi()){
            $this->renderApi();
        } else {
            if ($userDefault) {
                $this->renderFile();
            }
        }
    }

    /**
     * 是否已渲染
     * @return mixed
     */
    public function hasRender()
    {
        return $this->_hasRender;
    }

    /**
     * 渲染前置事件
     * @return mixed
     */
    public function beforeRender()
    {}

    /**
     * 渲染后置事件
     */
    public function afterRender()
    {}

    /**
     * 渲染模板
     * @param null $file
     * @param array $vars
     * @param bool $required
     */
    public function renderFile($file = null, array $vars = array(), $required = true)
    {
        $this->response->setContentType($this->_contentType);
        $this->formManager->setToken();

        if (empty($file)) {
            $tpl = $this->view->getTpl();
            if (empty($tpl)) {
                $this->view->setTpl(ocService()->app->getRoute('action'));
            }
        }

        $this->fire(self::EVENT_BEFORE_RENDER);
        $content = $this->view->renderFile($file, $vars, $required);
        $this->view->outputFile($content);
        $this->fire(self::EVENT_AFTER_RENDER);

        $this->_hasRender = true;
    }

    /**
     * 渲染API数据
     */
    public function renderApi()
    {
        $this->response->setContentType($this->_contentType);
        $this->formManager->setToken();

        if (!$this->response->getOption('statusCode')) {
            if ($this->_result['status'] == 'success') {
                $this->response->setStatusCode(Response::STATUS_OK);
            } else {
                $this->response->setStatusCode(Response::STATUS_SERVER_ERROR);
            }
        }

        $this->fire(self::EVENT_BEFORE_RENDER);
        $content = $this->view->renderApi($this->_result);
        $this->view->outputApi($content);
        $this->fire(self::EVENT_AFTER_RENDER);

        $this->_hasRender = true;
    }

    /**
     * 设置API结果
     * @param $data
     * @param $message
     * @param $status
     */
    public function setResult($data, $message = null, $status = 'success')
    {
        if (is_string($message)) {
            $message = $this->lang->get($message);
        }

        $this->_result = array(
            'status' => $status,
            'code' => $message['code'],
            'message' => $message['message'],
            'body' => $data
        );
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
            $this->_submitMethod = $method;
        }
        return $this->_submitMethod;
    }

    /**
     * 设置和获取是否表单提交
     * @param bool $isFormSubmit
     * @return bool
     */
    public function isFormSubmit($isFormSubmit = null)
    {
        if (isset($isFormSubmit)) {
            $this->_isFormSubmit = $isFormSubmit ? true : false;
        } else {
            return $this->_isFormSubmit;
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
        $data = $this->_submitMethod == 'post' ? $_POST : $_GET;
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
            $name = ocService()->app->getRoute('controller');
            $model = $this->model();
        }

        $form = $this->formManager->get($name);
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
     * 开启/关闭/检测表单验证功能
     * @param null $check
     * @return bool
     */
    public function isCheckForm($check = null)
    {
        if ($check === null) {
            return $this->_checkForm;
        }
        $this->_checkForm = $check ? true : false;
    }

    /**
     * 数据模型字段验证
     * @param $data
     * @param $model
     * @param Validator|null $validator
     * @return mixed
     */
    public function validate($data, $model, Validator &$validator = null)
    {
        $validator = $validator ? : $this->validator;

        if (is_object($model)) {
            if ($model instanceof DatabaseModel) {
                $class = $model->getClass();
            } else {
                ocService()->error->show('fault_model_object');
            }
        } else {
            $class = $model;
        }

        $model =  new $class();
        $data = $model->mapData($data, $class);
        $rules = $model->getConfig('VALIDATE', null, $class);
        $lang = $model->etConfig('LANG', null, $class);

        $result = $validator
            ->setRules($rules)
            ->setLang($lang)
            ->validate($data);

        return $result;
    }

    /**
     * 表单检测
     */
    public function checkForm()
    {
        $this->isFormSubmit();
        if (!($this->_isFormSubmit && $this->_checkForm && $this->formManager->get()))
            return true;

        $tokenTag  = $this->formToken->getTokenTag();
        $postToken = $this->getSubmitData($tokenTag);
        $postForm = $this->formManager->getSubmitForm($postToken);

        if ($postForm) {
            $data = $this->getSubmitData();
            $this->formManager->validate($postForm, $data);
        }

        return true;
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
                . ucfirst(ocService()->app->getRoute('controller'));
        }

        if (isset($this->_models[$class])) {
            $model = $this->_models[$class];
            if (is_object($model) && $model instanceof ModelBase) {
                return $model;
            }
        }

        $this->_models[$class] = new $class();
        return $this->_models[$class];
    }

    /**
     * 获取不存在的属性时
     * @param string $key
     * @return array|mixed
     */
	public function _none($key)
	{
		if ($instance = $this->getService($key)) {
			return $instance;
		}

        ocService()->error->show('no_property', array($key));
	}
}
