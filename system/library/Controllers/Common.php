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
use Ocara\Core\Event;
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
        return self::$controllerType ? ucfirst(self::$controllerType) : static::CONTROLLER_TYPE_COMMON;
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
     * @throws Exception
     */
    public function doAction($actionMethod)
    {
        if (!$this->isPostSubmit()) {
            if (method_exists($this, 'isSubmit')) {
                $this->isPostSubmit($this->isSubmit());
            } elseif ($this->request->isPostSubmit()) {
                $this->isPostSubmit(true);
            }
        }

        if ($this->isActionClass()) {
            $this->doClassAction();
        } else {
            $this->doFunctionAction($actionMethod);
        }

        $this->fire(self::EVENT_AFTER_ACTION);
    }

    /**
     * 执行动作函数方法实体
     * @param $actionMethod
     */
    protected function doFunctionAction($actionMethod)
    {
        $result = $this->$actionMethod();

        if ($this->request->isAjax()) {
            $this->isApi(true);
            $this->render($result);
        } elseif ($this->isPostSubmit()) {
            $this->formManager->clearToken();
            $this->render($result, false);
        } else {
            $this->formManager->bindToken();
            $this->render();
        }
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

        if ($this->request->isAjax()) {
            $this->checkForm();
            $result = method_exists($this, 'api') ? $this->api() : null;
            $this->isApi(true);
            $this->render($result);
        } elseif ($this->isPostSubmit() && method_exists($this, 'submit')) {
            $this->checkForm();
            $result = $this->submit();
            $this->formManager->clearToken();
            $this->render($result, false);
        } else {
            $this->formManager->bindToken();
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

        return $this->isApi;
    }

    /**
     * 渲染API
     * @param mixed $result
     * @param bool $useDefault
     */
    public function render($result = null, $useDefault = true)
    {
        if ($this->hasRender() || $this->response->isSent()) return;

        if ($this->isApi()) {
            $this->renderApi($result);
        } else {
            if ($useDefault) {
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
        $this->view->assign('route', $this->getRoute());
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
     * @param mixed $data
     * @param string $message
     * @param string $status
     */
    public function renderApi($data = null, $message = OC_EMPTY, $status = 'success')
    {
        if (!is_array($message)) {
            $message = $this->lang->get($message);
        }

        $this->result = $this->api->getResult($data, $message, $status);

        if ($this->contentType) {
            $this->response->setContentType($this->contentType);
        }

        $this->fire(self::EVENT_BEFORE_RENDER_API);

        $content = $this->view->renderApi($this->result);
        $this->view->outputApi($content);

        $this->fire(self::EVENT_AFTER_RENDER_API);
        $this->hasRender = true;
    }

    /**
     * 渲染前置事件
     * @throws Exception
     */
    public function beforeRenderApi()
    {
        if (!$this->response->getHeaderOption('statusCode')) {
            if ($this->result['status'] == 'success') {
                $this->response->setStatusCode(Response::STATUS_OK);
            } else {
                $this->response->setStatusCode(Response::STATUS_SERVER_ERROR);
            }
        }

        if (!ocConfig(array('API', 'send_header_code'), 0)) {
            $this->response->setStatusCode(Response::STATUS_OK);
            $this->result['status'] = $this->response->getHeaderOption('statusCode');
        }
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
}