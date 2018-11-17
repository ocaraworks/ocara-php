<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 异常处理类ExceptionHandler - 工厂类
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use \ErrorException;
use Ocara\Core\Basis;

defined('OC_PATH') or exit('Forbidden!');

class ExceptionHandler extends Basis
{
    protected $_error;

    /**
     * 错误处理
     * @param $exception
     * @throws \Ocara\Exceptions\Exception
     */
    public function exceptionHandler($exception)
    {
        $this->_error = ocGetExceptionData($exception);
        $this->report($exception);
        $this->handler($exception);
    }

    /**
     * 程序错误
     * @param $level
     * @param $message
     * @param $file
     * @param $line
     * @param string $context
     * @return bool
     * @throws \Ocara\Exceptions\Exception
     */
    public function errorHandler($level, $message, $file, $line, $context = '')
    {
        try {
            throw new ErrorException($message, $level, $level, $file, $line);
        } catch (ErrorException $exception) {
            $exceptErrors = ocForceArray(ocConfig('ERROR_HANDLER.except_error_list', array()));
            if (!in_array($level, $exceptErrors)) {
                $handler = new static();
                $handler->exceptionHandler($exception);
            }
        }

        return false;
    }

    /**
     * 错误处理
     * @param $exception
     * @throws \Ocara\Exceptions\Exception
     */
    public function handler($exception)
    {
        $response = ocService('response', true);

        if (!$response->getOption('statusCode')) {
            $response->setStatusCode(Response::STATUS_SERVER_ERROR);
        }

        if (ocService('request', true)->isAjax()) {
            $this->_ajaxError();
        } else {
            $defaultErrorOutput = ocConfig('SYSTEM_SINGLETON_SERVICE_CLASS.errorOutput');
            ocService('errorOutput', $defaultErrorOutput)->display($this->_error);
        }

        $response->send();
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
    protected function _ajaxError()
    {
        $message = array();
        $message['code'] = $this->_error['code'];
        $message['message'] = $this->_error['message'];

        ocService('ajax', true)->ajaxError($message);
    }
}