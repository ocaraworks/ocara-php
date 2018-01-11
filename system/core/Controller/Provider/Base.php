<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   控制器提供器基类Base
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Controller\Provider;

use Ocara\ServiceProvider;
use Ocara\Config;
use Ocara\Lang;
use Ocara\Database;

defined('OC_PATH') or exit('Forbidden!');

class Base extends ServiceProvider
{
    protected $_models = array();
    protected $_ajaxContentType;

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
        Config::set('AJAX.response_error_code', $value);
    }

	/**
     * Ajax返回数据
     * @param string $data
     * @param string $message
     */
	public function ajaxReturn($data = '', $message = '')
    {
        if (is_array($message)) {
            list($text, $params) = $message;
            $message = Lang::get($text, $params);
        } else {
            $message = Lang::get($message);
        }

        $contentType = $this->_ajaxContentType;
        if (!$contentType) {
            $contentType = ocConfig('DEFAULT_AJAX_CONTENT_TYPE', 'json');
        }

        $this->response->setContentType($contentType);
        $this->view->ajaxOutput($data, $message);
        method_exists($this, '_after') && $this->_after();

        die();
    }

    /**
     * 获取或设置Model-静态属性保存
     * @param string $class
     */
    public function model($class = null)
    {
        if (empty($class)) {
            $class = '\Model\Main\\' . ucfirst($this->getRoute('controller'));
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
     * 注册基本组件
     * @param array $data
     */
    public function register($data = array())
    {
        $this->_container->bindSingleton('db', function(){
            Database::create();
        });

        $route = array();
        if (!empty($data['route'])) {
            $this->setRoute($data['route']);
            $route = array('route' => $this->getRoute());
        }

        $services = ocConfig('CONTROLLER_SERVICE_CLASS.Common');
        foreach ($services as $name => $class) {
            $this->_container->bindSingleton($name, $class, array(), $route);
        }
    }
}