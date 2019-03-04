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

class Controller extends serviceProvider implements ControllerInterface
{
	/**
	 * @var $_provider 控制器提供者
     * @var $_isFormSubmit 是否POST提交
     * @var $_checkForm 是否检测表单
	 */
	protected $_models;
	protected $_provider;
    protected $_isFormSubmit = null;
    protected $_submitMethod = 'post';
    protected $_checkForm = true;

    protected static $_providerType;

    const EVENT_AFTER = 'after';
    const EVENT_AFTER_CREATE_FORM = 'afterCreateForm';

	/**
	 * 初始化设置
	 */
	public function init()
	{
	    $route = $this->getRoute();
        $provider = Route::getProviderClass(self::providerType());

        if (!ocClassExists($provider)){
            ocService()->error->show('not_exists_class', $provider);
        }

        $this->_provider = new $provider(compact('route'));
        $this->_provider->bindEvents($this);
		$this->config->set('SOURCE.ajax.return_result', array($this->_provider, 'formatAjaxResult'));

		method_exists($this, '__start') && $this->__start();
		method_exists($this, '__module') && $this->__module();
		method_exists($this, '__control') && $this->__control();
	}

    /**
     * 注册事件
     */
    public function registerEvents()
    {
        $this->event(self::EVENT_AFTER_CREATE_FORM)
             ->setDefault(array($this, 'afterCreateForm'));

        $this->event(self::EVENT_AFTER)
             ->setDefault(array($this, 'after'));
    }

	/**
	 * 获取当前的提供者
	 * @return 控制器提供者
	 */
	public function provider()
	{
		return $this->_provider;
	}

    /**
     * 获取提供者类型
     */
	public static function providerType()
    {
	    return self::$_providerType ? ucfirst(self::$_providerType): 'Common';
    }

    /**
     * 获取当前路由
     * @param string $name
     * @return mixed
     */
	public function getRoute($name = null)
    {
	    return call_user_func_array(array(ocService()->app, 'getRoute'), func_get_args());
    }

	/**
	 * 执行动作
	 * @param string $actionMethod
	 */
	public function doAction($actionMethod)
	{
        $doWay = $this->_provider->getDoWay();

		if (!$this->isFormSubmit()) {
			if (method_exists($this, 'isSubmit')) {
				$this->isFormSubmit($this->isSubmit());
			} elseif ($this->submitMethod() == 'post') {
				$this->isFormSubmit($this->request->isPost());
			}
		}

		if ($doWay == 'common') {
            $this->doCommonAction($actionMethod);
		} elseif($doWay == 'api') {
            $this->doApiAction($actionMethod);
		}

        $this->fire(self::EVENT_AFTER);
	}

    /**
     * 执行动作
     * @param $actionMethod
     */
	public function doCommonAction($actionMethod)
	{
	    if ($actionMethod == '__action') {
            method_exists($this, '__action') && $this->__action();
            method_exists($this, 'registerForms') && $this->registerForms();
            $this->checkForm();

            if ($this->request->isAjax()) {
                $result = null;
                if (method_exists($this, 'ajax')) {
                    $result = $this->ajax();
                }
                if (!$this->_provider->hasRender()) {
                    $this->_provider->renderAjax($result);
                }
            } elseif ($this->isFormSubmit() && method_exists($this, 'submit')) {
                $this->submit();
                $this->_provider->formManager->clearToken();
            } else{
                method_exists($this, 'display') && $this->display();
                if (!$this->_provider->hasRender()) {
                    $this->_provider->renderFile();
                }
            }
        } else {
            $this->$actionMethod();
            if (!$this->_provider->hasRender()) {
                $this->_provider->renderFile();
            }
        }
	}

	/**
	 * 执行动作
	 * @param string $actionMethod
	 */
	public function doApiAction($actionMethod)
	{
		if ($actionMethod) {
            $result = $this->$actionMethod();
		} else {
            $result = $this->__action();
		}

		$this->_provider->renderApi($result);
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
     * @throws \Ocara\Exceptions\Exception
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
     * @throws \Ocara\Exceptions\Exception
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
     * 后置处理
     * @param Event $event
     */
    public function after()
    {}

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
     * @throws \Ocara\Exceptions\Exception
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

        $data = DatabaseModel::mapData($data, $class);
        $rules = DatabaseModel::getConfig('VALIDATE', null, $class);
        $lang = DatabaseModel::getConfig('LANG', null, $class);
        $result = $validator->setRules($rules)->setLang($lang)->validate($data);

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
     * @throws \Ocara\Exceptions\Exception
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
     * @throws \Ocara\Exceptions\Exception
     */
	public function &__get($key)
	{
        if ($this->hasProperty($key)) {
            $value = &$this->getProperty($key);
            return $value;
        }

		if ($instance = $this->_provider->getService($key)) {
			return $instance;
		}

        ocService()->error->show('no_property', array($key));
	}

    /**
     * 调用未定义的方法时
     * @param string $name
     * @param array $params
     * @return mixed
     * @throws \Ocara\Exceptions\Exception
     */
	public function __call($name, $params)
	{
        if (isset($this->_traits[$name])) {
            return call_user_func_array($this->_traits[$name], $params);
        }

        if (is_object($this->_provider)) {
            return call_user_func_array(array(&$this->_provider, $name), $params);
        }

        ocService()->error->show('no_method', array($name));
	}
}
