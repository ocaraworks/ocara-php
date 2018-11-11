<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   普通视图类View
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Views;

use Ocara\Core\ViewBase;
use Ocara\Interfaces\View as ViewInterfaces;

defined('OC_PATH') or exit('Forbidden!');

class Api extends ViewBase implements ViewInterfaces
{
    /**
     * 输出内容
     * @param array $data
     */
    public function output($data)
    {
        ocService()->response->setContentType($data['contentType']);
        ocService()->ajax->render('success', $data['message'], $data['data']);
    }

    /**
     * 渲染结果
     * @param $status
     * @param array $message
     * @param string $body
     * @throws \Ocara\Exceptions\Exception
     */
    public function render($status, array $message = array(), $body = OC_EMPTY)
    {
        $services = ocService();
        if (is_string($message)) {
            $message = $services->lang->get($message);
        }

        $result['status'] 	= $status;
        $result['code']    	= $message['code'];
        $result['message'] 	= $message['message'];
        $result['body']    	= $body;

        if ($callback = ocConfig('SOURCE.ajax.return_result', null)) {
            $result = call_user_func_array($callback, array($result));
        }

        $response = $services->response;
        $statusCode = $response->getOption('statusCode');

        if (!$statusCode && !ocConfig('API.is_send_error_code', 0)) {
            $response->setStatusCode(Response::STATUS_OK);
            $result['statusCode'] = $response->getOption('statusCode');
        }

        $contentType = $response->getOption('contentType');
        switch ($contentType)
        {
            case 'json':
                $content = json_encode($result);
                break;
            case 'xml':
                $content = $this->getXmlResult($result);
                break;
        }

        $response->setBody($content);
    }
}