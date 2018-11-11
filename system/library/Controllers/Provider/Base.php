<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   控制器提供器基类Base
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Controllers\Provider;

use Ocara\Core\DatabaseFactory;
use Ocara\Core\ServiceProvider;

defined('OC_PATH') or exit('Forbidden!');

class Base extends ServiceProvider
{
    protected $_models = array();
    protected $_ajaxContentType;
    protected $_message;

    /**
     * 设置Ajax返回的数据格式
     * @param $contentType
     */
    public function setAjaxContentType($contentType)
    {
        $this->_ajaxContentType = $contentType;
    }

    /**
     * 是否在HTTP头部返回错误码
     * @param bool $value
     */
    public function setAjaxResponseErrorCode($value)
    {
        $value = $value ? 1 : 0;
        $this->config->set('AJAX.response_error_code', $value);
    }

    /**
     * Ajax返回数据
     * @param string $data
     * @param string $message
     * @throws \Ocara\Exceptions\Exception
     */
	public function ajaxReturn($data = '', $message = '')
    {
        if (is_array($message)) {
            list($text, $params) = $message;
            $message = $this->lang->get($text, $params);
        } else {
            $message = $this->lang->get($message);
        }

        $contentType = $this->_ajaxContentType;
        if (!$contentType) {
            $contentType = ocConfig('DEFAULT_AJAX_CONTENT_TYPE', 'json');
        }

        $this->response->setContentType($contentType);
        $this->ajax->ajaxSuccess($data, $message);
        method_exists($this, '_after') && $this->_after();
    }

    /**
     * 渲染模板
     * @param string $file
     * @param array $vars
     * @return mixed
     */
    public function renderFile($file = null, array $vars = array())
    {
        $this->formManager->setToken();

        if (empty($file)) {
            $tpl = $this->view->getTpl();
            if (empty($tpl)) {
                $this->view->setTpl(ocService()->app->getRoute('action'));
            }
        }

        $content = $this->view->render($file, $vars, false);
        $this->view->output(compact('content'));
        $this->event(self::EVENT_AFTER)->fire();
        $this->_hasRender = true;
    }

    /**
     * 渲染API数据
     * @param string $data
     */
    public function renderApi($data = '')
    {
        $message = $this->_message;
        $contentType = $this->_ajaxContentType;

        if (is_array($message)) {
            list($text, $params) = $message;
            $message = ocService()->lang->get($text, $params);
        } else {
            $message = ocService()->lang->get($message);
        }

        $this->view->output(compact('contentType', 'message', 'data'));
        $this->event(self::EVENT_AFTER)->fire();
        $this->_hasRender = true;
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
     * 设置返回消息
     * @param $message
     */
    public function setMessage($message)
    {
        $this->_message = $message;
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
}