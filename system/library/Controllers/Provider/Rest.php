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
use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class Rest extends Base
{
    /**
     * @var $_message 返回消息
     */
    protected $hyperMediaLink;

    /**
     * 初始化设置
     */
    public function init()
    {
        $this->request->setAjax();
        $this->response->setContentType(ocConfig(array('CONTROLLERS', 'rest', 'content_type'),'json'));
        $this->session->boot();
        $this->isSendApiErrorCode(true);
        $this->plugin = $this->view;
    }

    /**
     * 获取动作执行方式
     * @return string
     */
    public function getDoWay()
    {
        return 'api';
    }

    /**
     * 设置Hypermedia
     * @param array $linkInfo
     */
    public function setMediaLink(array $linkInfo)
    {
        $this->hyperMediaLink = $linkInfo;
    }

    /**
     * 获取当前请求的ID
     * @return mixed
     * @throws Exception
     */
    public function getRequestId()
    {
        return $this->request->getGet(ocConfig(array('CONTROLLERS', 'rest', 'id_param'), 'id'));
    }

    /**
     * 输出内容（回调函数）
     * @param $result
     * @return mixed
     * @throws Exception
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