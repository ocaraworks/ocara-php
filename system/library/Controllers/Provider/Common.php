<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   普通控制器提供器类Common
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Controllers\Provider;

use Ocara\Interfaces\Event;
use Ocara\Models\Database as DatabaseModel;
use Ocara\Controllers\Provider\Base;

defined('OC_PATH') or exit('Forbidden!');

class Common extends Base
{
    /**
     * @var $_isSubmit 是否POST提交
     * @var $_checkForm 是否检测表单
     */
    private $_isSubmit = null;
    private $_submitMethod = 'post';
    private $_checkForm = true;

    /**
     * 初始化设置
     */
    public function init()
    {
        $this->session->boot();
        $this->setAjaxResponseErrorCode(false);
        $this->event('afterCreateForm')->append(array($this, 'afterCreateForm'));
        $this->_plugin = $this->view;
    }

    /**
     * 获取动作执行方式
     */
    public function getDoWay()
    {
        return 'common';
    }

    /**
     * 设置和获取表单提交方式
     * @param null $method
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
     * @param bool $isSubmit
     * @return bool
     */
    public function isSubmit($isSubmit = null)
    {
        if (isset($isSubmit)) {
            $this->_isSubmit = $isSubmit ? true : false;
        } else {
            return $this->_isSubmit;
        }
    }

    /**
     * 获取表单提交的数据
     * @param null $key
     * @param null $default
     * @return array|null|string
     */
    public function getSubmit($key = null, $default = null)
    {
        $data = $this->_submitMethod == 'post' ? $_POST : $_GET;
        $data = ocService()->request->getRequestValue($data, $key, $default);
        return $data;
    }

    /**
     * 打印模板
     * @param string $file
     * @param array $vars
     */
    public function display($file = null, array $vars = array())
    {
        $content = $this->render($file, $vars);
        $this->view->output(array('content' => $content));
        $this->event('_after')->fire();

        die();
    }

    /**
     * 渲染模板
     * @param string $file
     * @param array $vars
     * @return mixed
     */
    public function render($file = null, array $vars = array())
    {
        $this->formManager->setToken();

        if (empty($file)) {
            $tpl = $this->view->getTpl();
            if (empty($tpl)) {
                $this->view->setTpl(ocService()->app->getRoute('action'));
            }
        }

        return $this->view->render($file, $vars, false);
    }

    /**
     * 获取表单并自动验证
     * @param null $name
     * @return $this|Form
     * @throws \Ocara\Core\Exception
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
            $this->event('afterCreateForm')->fire(array($name, $form));
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
     * 设置AJAX返回格式（回调函数）
     * @param $result
     */
    public function formatAjaxResult($result)
    {
        if ($result['status'] == 'success') {
            $this->response->setStatusCode(Response::STATUS_OK);
            return $result;
        } else {
            if (!$this->response->getOption('statusCode')) {
                $this->response->setStatusCode(Response::STATUS_SERVER_ERROR);
            }
            return $result;
        }
    }

    /**
     * 数据模型字段验证
     * @param array $data
     * @param string|object $model
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
        $this->isSubmit();
        if (!($this->_isSubmit && $this->_checkForm && $this->formManager->get()))
            return true;

        $tokenTag  = $this->formToken->getTokenTag();
        $postToken = $this->getSubmit($tokenTag);
        $postForm = $this->formManager->getSubmitForm($postToken);

        if ($postForm) {
            $data = $this->getSubmit();
            $this->formManager->validate($postForm, $data);
        }

        return true;
    }
}