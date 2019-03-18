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
use Ocara\Core\Base;

defined('OC_PATH') or exit('Forbidden!');

class ExceptionHandler extends Base
{
    const EVENT_BEFORE_OUTPUT = 'beforeOutput';
    const EVENT_OUTPUT = 'output';
    const EVENT_AFTER_OUTPUT = 'afterOutput';

    public function registerEvents()
    {
        $this->event(self::EVENT_BEFORE_OUTPUT)
             ->setDefault(array($this, 'report'));

        $this->event(self::EVENT_OUTPUT)
             ->setDefault(array($this, 'output'));
    }

    /**
     * 错误处理
     * @param $exception
     */
    public function exceptionHandler($exception)
    {
        return $this->handler($exception);
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
            $exceptErrors = ocForceArray(ocConfig(array('ERROR_HANDLER', 'except_error_list'), array()));
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
     */
    public function handler($exception)
    {
        $response = ocService('response', true);

        if (!$response->getOption('statusCode')) {
            $response->setStatusCode(Response::STATUS_SERVER_ERROR);
        }

        $this->fire(self::EVENT_BEFORE_OUTPUT, array($exception));
        $this->fire(self::EVENT_OUTPUT, array($exception));
        $this->fire(self::EVENT_AFTER_OUTPUT, array($exception));

        $response->send(true);
    }

    /**
     * 输出错误
     * @param $object
     * @param $event
     * @param $exception
     * @throws \Ocara\Exceptions\Exception
     */
    public function output($exception, $event, $object)
    {
        $error = ocGetExceptionData($exception);
        if (ocService('request', true)->isAjax()) {
            $this->_ajaxError($error);
        } else {
            $defaultOutput = ocConfig(array('SYSTEM_SINGLETON_SERVICE_CLASS', 'errorOutput'));
            ocService('errorOutput', $defaultOutput)->display($error);
        }
    }

    /**
     * 错误报告
     * @param $exception
     * @param $event
     * @param $object
     * @throws \Ocara\Exceptions\Exception
     */
    public function report($exception, $event, $object)
    {
        $error = ocGetExceptionData($exception);
        ocService('log', true)
            ->write(
                $error['message'],
                $error['traceInfo'],
                'exception'
            );
    }

    /**
     * Ajax处理
     * @param $error
     */
    protected function _ajaxError($error)
    {
        $message = array();
        $message['code'] = $error['code'];
        $message['message'] = $error['message'];

        ocService('ajax', true)
            ->ajaxError($message);
    }
}