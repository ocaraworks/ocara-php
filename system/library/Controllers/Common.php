<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   普通控制器类Common
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Controllers;

use Ocara\Core\ControllerBase;
use Ocara\Core\Response;
use Ocara\Exceptions\Exception;
use Ocara\Interfaces\Controller as ControllerInterface;

class Common extends ControllerBase implements ControllerInterface
{
    /**
     * @var bool $isApi
     */
    protected $isApi = false;

    const EVENT_BEFORE_RENDER_FILE = 'beforeRenderFile';
    const EVENT_AFTER_RENDER_FILE = 'afterRenderFile';
    const EVENT_BEFORE_RENDER_API = 'beforeRenderApi';
    const EVENT_AFTER_RENDER_API = 'afterRenderApi';

    /**
     * 获取控制器类型
     */
    public static function controllerType()
    {
        return self::$controllerType ? ucfirst(self::$controllerType): 'Common';
    }

    /**
     * 注册事件
     */
    public function registerEvents()
    {
        parent::registerEvents();

        $this->event(self::EVENT_BEFORE_RENDER_FILE)
             ->setDefault(array($this, 'beforeRenderFile'));

        $this->event(self::EVENT_AFTER_RENDER_FILE)
             ->setDefault(array($this, 'afterRenderFile'));

        $this->event(self::EVENT_BEFORE_RENDER_API)
             ->setDefault(array($this, 'beforeRenderApi'));

        $this->event(self::EVENT_AFTER_RENDER_API)
             ->setDefault(array($this, 'afterRenderApi'));
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
            $this->doClassAction();
        } else {
            $this->$actionMethod();
            $this->render();
        }

        $this->fire(self::EVENT_AFTER_ACTION);
    }

    /**
     * 执行动作类实例
     */
    protected function doClassAction()
    {
        if (method_exists($this, '__action')) {
            $this->__action();
        }

        if (method_exists($this, 'registerForms')) {
            $this->registerForms();
        }

        $this->checkForm();
        $result = null;

        if ($this->request->isAjax()) {
            if (method_exists($this, 'api')) {
                $result = $this->api();
            }
            $this->render($result, false);
        } elseif ($this->isFormSubmit() && method_exists($this, 'submit')) {
            $result = $this->submit();
            $this->formManager->clearToken();
            $this->render($result, false);
        } else {
            if (method_exists($this, 'display')) {
                $this->display();
            }
            $this->render();
        }
    }

    /**
     * 是否API
     * @param bool $isApi
     * @return bool
     */
    public function isApi($isApi = true)
    {
        if (func_get_args()) {
            $this->isApi = $isApi ? true : false;
        }

        return $this instanceof Api || $this->isApi;
    }

    /**
     * 渲染API
     * @param null $result
     * @param bool $userDefault
     */
    public function render($result = null, $userDefault = true)
    {
        if ($this->hasRender()) return;

        if ($this->isApi()){
            $this->renderApi($result);
        } else {
            if ($userDefault) {
                $this->renderFile();
            } else {
                $this->response->setBody($result);
            }
        }
    }

    /**
     * 渲染模板
     * @param null $file
     * @param array $vars
     * @param bool $required
     */
    public function renderFile($file = null, array $vars = array(), $required = true)
    {
        $this->response->setContentType($this->contentType);

        if (empty($file)) {
            $tpl = $this->view->getTpl();
            if (empty($tpl)) {
                $this->view->setTpl($this->getRoute('action'));
            }
        }

        $this->fire(self::EVENT_BEFORE_RENDER_FILE);
        $content = $this->view->renderFile($file, $vars, $required);
        $this->view->outputFile($content);
        $this->fire(self::EVENT_AFTER_RENDER_FILE);

        $this->hasRender = true;
    }

    /**
     * 渲染API数据
     * @param null $data
     * @param null $message
     * @param string $status
     */
    public function renderApi($data = null, $message = null, $status = 'success')
    {
        if (is_string($message)) {
            $message = $this->lang->get($message);
        }

        $params = array($data, $message, $status);

        $this->response->setContentType($this->contentType);
        $this->fire(self::EVENT_BEFORE_RENDER_API, $params);

        $content = $this->view->renderApi($this->result);
        $this->view->outputApi($content);

        $this->fire(self::EVENT_AFTER_RENDER_API, $params);
        $this->hasRender = true;
    }

    /**
     * 渲染前置事件
     * @param $data
     * @param $message
     * @param $status
     * @throws Exception
     */
    public function beforeRenderApi($data, $message, $status)
    {
        $this->result = $this->api->getResult($data, $message, $status);

        if (!$this->response->getOption('statusCode')) {
            if ($this->result['status'] == 'success') {
                $this->response->setStatusCode(Response::STATUS_OK);
            } else {
                $this->response->setStatusCode(Response::STATUS_SERVER_ERROR);
            }
        }

        if (!ocConfig(array('API', 'send_header_code'), 0)) {
            $this->response->setStatusCode(Response::STATUS_OK);
            $this->result['status'] = $this->response->getOption('status');
        }
    }
}