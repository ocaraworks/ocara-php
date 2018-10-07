<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Restful控制器提供器类Rest
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Controllers\Provider;

use Ocara\Ocara;
use Ocara\Response;
use Ocara\Controllers\Provider\Base;

defined('OC_PATH') or exit('Forbidden!');

class Rest extends Base
{
    /**
     * @var $_message 返回消息
     */
    protected $_message;
    protected $_hyperMediaLink;

    /**
     * 初始化设置
     */
    public function init()
    {
        $this->request->setAjax();
        $this->response->setContentType(ocConfig('CONTROLLERS.rest.content_type','json'));
        $this->session->init();
        $this->setAjaxResponseErrorCode(true);
        $this->_plugin = $this->view;
    }

    /**
     * 获取动作执行方式
     */
    public function getDoWay()
    {
        return 'ajax';
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
     * 设置Hypermedia
     * @param $linkInfo
     */
    public function setMediaLink(array $linkInfo)
    {
        $this->_hyperMediaLink = $linkInfo;
    }

    /**
     * Ajax返回数据
     * @param string $data
     */
    public function display($data = '')
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
        $this->event('_after')->fire();
        die();
    }

    /**
     * 获取当前请求的ID
     * @return null|string
     */
    public function getRequestId()
    {
        return ocService()->request->getGet(ocConfig('CONTROLLERS.rest.id_param', 'id'));
    }

    /**
     * 输出内容（回调函数）
     * @param $result
     */
    public function formatAjaxResult($result)
    {
        if ($result['status'] == 'success') {
            $successCode = strtr(
                $this->getRoute('action'),
                ocConfig('CONTROLLERS.rest.success_code_map')
            );
            $this->response->setStatusCode($successCode);
            return $result['body'];
        } else {
            if (!$this->response->getOption('statusCode')) {
                $this->response->setStatusCode(Response::STATUS_SERVER_ERROR);
            }
            return $result;
        }
    }
}