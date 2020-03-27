<?php
/**
 * 应用控制器基类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use \ReflectionException;
use Ocara\Exceptions\Exception;
use Ocara\Models\DatabaseModel;

abstract class ControllerBase extends serviceProvider
{
    /**
     * @var $isPostSubmit 是否POST提交
     * @var $checkForm 是否检测表单
     */
    private $isActionClass = false;

    protected $route;
    protected $models;
    protected $isPostSubmit = false;
    protected $checkForm = true;
    protected $hasRender = false;
    protected $contentType;
    protected $result;
    protected static $controllerType;

    const CONTROLLER_TYPE_COMMON = 'Common';
    const CONTROLLER_TYPE_API = 'Api';
    const CONTROLLER_TYPE_REST = 'Rest';
    const CONTROLLER_TYPE_TASK = 'Task';

    const EVENT_AFTER_ACTION = 'afterAction';
    const EVENT_AFTER_CREATE_FORM = 'afterCreateForm';

    /**
     * 初始化设置
     * @param bool $isActionClass
     * @throws Exception
     */
    public function initialize($isActionClass)
    {
        $this->isActionClass = $isActionClass;

        if ($this instanceof \Ocara\Controllers\Api) {
            ocService()->exceptionHandler->setResponseFormat(ExceptionHandler::RESPONSE_FORMAT_API);
        } else {
            ocService()->exceptionHandler->setResponseFormat(ExceptionHandler::RESPONSE_FORMAT_COMMON);
        }

        $this->bindEventHandler($this);

        if (!in_array(PHP_SAPI, array('cli'))) $this->session->boot();

        $this->formManager->setRoute($this->getRoute());

        if (!in_array(PHP_SAPI, array('cli'))) $this->setPlugin($this->view);

        method_exists($this, '__start') && $this->__start();
        method_exists($this, '__module') && $this->__module();
        method_exists($this, '__control') && $this->__control();
    }

    /**
     * 注册基本组件
     */
    public function register()
    {
        $this->container->bindSingleton('db', function () {
            ocService()->databases->make();
        });

        $services = array_merge(
            ocConfig(array('CONTROLLER_SERVICE_CLASS', 'common'), array()),
            ocConfig(array('CONTROLLER_SERVICE_CLASS', 'special', static::controllerType()), array())
        );

        foreach ($services as $name => $class) {
            $this->container->bindSingleton($name, $class, array($this->getRoute()));
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
    }

    /**
     * 当前是否是
     * @return bool
     */
    public function isActionClass()
    {
        return $this->isActionClass;
    }

    /**
     * 获取提供器类
     * @param string $controllerType
     * @return string
     * @throws Exception
     */
    public static function getControllerClass($controllerType)
    {
        $providers = ocConfig(array('ROUTE', 'providers'), array());

        if (isset($providers[$controllerType])) {
            $class = $providers[$controllerType];
        } else {
            $class = "Ocara\\Controllers\\{$controllerType}";
        }

        return $class;
    }

    /**
     * 获取提供器特性类
     * @param string $controllerType
     * @return string
     * @throws Exception
     */
    public static function getFeatureClass($controllerType)
    {
        $features = ocConfig(array('ROUTE', 'features'), array());

        if (isset($features[$controllerType])) {
            $class = $features[$controllerType];
        } else {
            $class = "Ocara\\Controllers\\Feature\\{$controllerType}";
        }

        return $class;
    }

    /**
     * 设置路由
     * @param array $route
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
     * 设置和获取是否表单提交
     * @param bool $isPostSubmit
     * @return bool
     */
    public function isPostSubmit($isPostSubmit = null)
    {
        if (func_num_args()) {
            $this->isPostSubmit = $isPostSubmit ? true : false;
        } else {
            return $this->isPostSubmit;
        }
    }

    /**
     * 发送响应输出
     * @param string $body
     * @return mixed
     */
    public function send($body = null)
    {
        $this->response->setBody($body);
        return $this->response->send();
    }

    /**
     * 获取表单并自动验证
     * @param string $name
     * @return Form|Form[]
     * @throws Exception
     * @throws ReflectionException
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
     * 开启/关闭/检测表单令牌功能
     * @param bool $check
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
     * @return bool|mixed
     * @throws Exception
     * @throws ReflectionException
     */
    public function checkForm()
    {
        if (!($this->isPostSubmit() && $this->checkForm && $this->formManager->getForm()))
            return true;

        $token = $this->request->getCommonRequest(FormManager::getTokenName());
        return $this->formManager->checkForm($token);
    }

    /**
     * 获取或设置Model-静态属性保存
     * @param string $class
     * @return DatabaseModel
     * @throws Exception
     */
    public function model($class = null)
    {
        if (empty($class)) {
            $class = 'app\model\database\\'
                . ucfirst($this->getRoute('controller'))
                . ocConfig('MODEL_SUFFIX');
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
}
