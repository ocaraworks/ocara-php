<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Restful控制器提供器类Rest
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Controllers\Provider;

use Ocara\Core\Response;
use Ocara\Controllers\Provider\Base;

defined('OC_PATH') or exit('Forbidden!');

class Rest extends Base
{
    /**
     * @var $_message 返回消息
     */
    protected $_hyperMediaLink;

    /**
     * 初始化设置
     */
    public function init()
    {
        $this->request->setAjax();
        $this->response->setContentType(ocConfig('CONTROLLERS.rest.content_type','json'));
        $this->session->boot();
        $this->isSendApiErrorCode(true);
        $this->_plugin = $this->view;
    }

    /**
     * 获取动作执行方式
     */
    public function getDoWay()
    {
        return 'api';
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
     * 获取当前请求的ID
     * @return mixed
     * @throws \Ocara\Exceptions\Exception
     */
    public function getRequestId()
    {
        return $this->request->getGet(ocConfig('CONTROLLERS.rest.id_param', 'id'));
    }

    /**
     * 输出内容（回调函数）
     * @param $result
     * @return mixed
     * @throws \Ocara\Exceptions\Exception
     */
    public function formatAjaxResult($result)
    {
        if ($result['status'] == 'success') {
            $successCode = strtr(
                ocService()->app->getRoute('action'),
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