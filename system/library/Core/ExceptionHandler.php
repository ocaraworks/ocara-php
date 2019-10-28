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
use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class ExceptionHandler extends Base
{
    protected $responseFormat;

    const EVENT_BEFORE_OUTPUT = 'beforeOutput';
    const EVENT_OUTPUT = 'output';
    const EVENT_AFTER_OUTPUT = 'afterOutput';

    const RESPONSE_FORMAT_API = 'api';
    const RESPONSE_FORMAT_COMMON = 'common';

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
     * @throws Exception
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

        if (!$response->getHeaderOption('statusCode')) {
            $response->setStatusCode(Response::STATUS_SERVER_ERROR);
        }

        $this->fire(self::EVENT_BEFORE_OUTPUT, array($exception));
        $this->fire(self::EVENT_OUTPUT, array($exception));
        $this->fire(self::EVENT_AFTER_OUTPUT, array($exception));

        $response->send();
    }

    /**
     * 设置内容响应返回格式
     * @param $responseFormat
     */
    public function setResponseFormat($responseFormat)
    {
        $this->responseFormat = $responseFormat;
    }

    /**
     * 输出错误
     * @param $exception
     * @param $event
     * @param $object
     * @throws Exception
     */
    public function output($exception, $event, $object)
    {
        $error = ocGetExceptionData($exception);

        $isAPi = ocConfig('DEFAULT_RESPONSE_FORMAT') == self::RESPONSE_FORMAT_API
            || $this->responseFormat == 'API'
            || ocService('request', true)->isAjax();

        if ($isAPi) {
            $this->apiError($error);
        } else {
            $response = ocService('response', true);
            $contentType = $response->getHeaderOption('contentType');
            if (!$contentType) {
                $response->setContentType('html');
            }
            $defaultOutput = ocConfig(array('SYSTEM_SINGLETON_SERVICE_CLASS', 'errorOutput'));
            ocService('errorOutput', $defaultOutput)->display($error);
        }
    }

    /**
     * 错误报告
     * @param $exception
     * @param $event
     * @param $object
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
     * @throws Exception
     */
    protected function apiError($error)
    {
        $response = ocService('response', true);
        $message = ocService()->api->getResult(OC_EMPTY, $error, 'error');
        $contentType = ocService()->response->getHeaderOption('contentType');

        if (!$contentType) {
            $contentType = ocConfig('API_CONTENT_TYPE', 'json');
            $response->setContentType($contentType);
        }

        $content = ocService('api', true)->format($message, $contentType);
        $response->setStatusCode(Response::STATUS_SERVER_ERROR);
        $response->setBody($content);
    }
}