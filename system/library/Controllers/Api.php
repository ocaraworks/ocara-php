<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   API普通控制器类Api
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Controllers;

use Ocara\Core\ControllerBase;
use Ocara\Core\Response;
use Ocara\Exceptions\Exception;
use Ocara\Interfaces\Controller as ControllerInterface;

class Api extends ControllerBase implements ControllerInterface
{
    const EVENT_BEFORE_RENDER = 'beforeRender';
    const EVENT_AFTER_RENDER = 'afterRender';

    /**
     * 获取控制器类型
     */
    public static function controllerType()
    {
        return self::$controllerType ? ucfirst(self::$controllerType): 'Api';
    }

    /**
     * 注册事件
     */
    public function registerEvents()
    {
        parent::registerEvents();

        $this->event(self::EVENT_BEFORE_RENDER)
             ->setDefault(array($this, 'beforeRender'));

        $this->event(self::EVENT_AFTER_RENDER)
             ->setDefault(array($this, 'afterRender'));
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
            $result = $this->$actionMethod();
            $this->render($result);
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

        if ($this->isFormSubmit() && method_exists($this, 'submit')) {
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
     * 渲染API
     * @param null $result
     */
    public function render($result = null)
    {
        if ($this->hasRender()) return;
        $this->renderApi($result);
    }

    /**
     * 渲染API数据
     * @param null $data
     * @param null $message
     * @param string $status
     */
    public function renderApi($data = null, $message = null, $status = 'success')
    {
        if (!is_array($message)) {
            $message = $this->lang->get($message);
        }

        $params = array($data, $message, $status);

        $this->response->setContentType($this->contentType);
        $this->fire(self::EVENT_AFTER_RENDER, $params);

        $content = $this->view->render($this->result);
        $this->view->output($content);

        $this->fire(self::EVENT_AFTER_RENDER, $params);
        $this->hasRender = true;
    }

    /**
     * 渲染前置事件
     * @param $data
     * @param $message
     * @param $status
     * @throws Exception
     */
    public function beforeRender($data, $message, $status)
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