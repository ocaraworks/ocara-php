<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 异常处理类ExceptionHandler - 工厂类
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

use Ocara\Basis;

defined('OC_PATH') or exit('Forbidden!');

class ExceptionHandler extends Basis
{
    protected $_error;

    /**
     * 错误处理
     * @param $exception
     */
    public function run($exception)
    {
        $this->_error = ocGetExceptionData($exception);
        $this->report($exception);
        $this->handler($exception);
    }

    /**
     * 错误处理
     * @param $exception
     */
    public function handler($exception)
    {
        $response = ocService('response', true);

        if (!$response->getOption('statusCode')) {
            $response->setStatusCode(Response::STATUS_SERVER_ERROR);
        }

        if (ocService('request', true)->isAjax()) {
            $this->_defaultAjaxHandler();
        } else {
            $defaultErrorOutput = ocConfig('SYSTEM_SINGLETON_SERVICE_CLASS.errorOutput');
            ocService('errorOutput', $defaultErrorOutput)->display($this->_error);
        }

        die();
    }

    /**
     * 错误报告
     * @param $exception
     */
    public function report($exception)
    {
        ocService('log', true)->write(
            $this->_error['message'],
            $this->_error['traceInfo'],
            'exception'
        );
    }

    /**
     * Ajax处理
     */
    protected function _defaultAjaxHandler()
    {
        $message = array();
        $message['code'] = $this->_error['code'];
        $message['message'] = $this->_error['message'];

        ocService('ajax', true)->show('error', $message);
    }
}