<?php
/**
 * 异常处理类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use \ErrorException;

use Ocara\Exceptions\Exception;

class ExceptionHandler extends Base
{
    protected $responseFormat;

    const EVENT_REPORT = 'report';
    const EVENT_BEFORE_OUTPUT = 'beforeOutput';
    const EVENT_OUTPUT = 'output';
    const EVENT_AFTER_OUTPUT = 'afterOutput';

    const RESPONSE_FORMAT_API = 'api';
    const RESPONSE_FORMAT_COMMON = 'common';

    /**
     * 事件注册
     * @throws Exception
     */
    public function registerEvents()
    {
        $this->event(self::EVENT_REPORT)
            ->appendAll(ocConfig(array('EVENTS', 'error', 'report'), array(array($this, 'report'))));

        $this->event(self::EVENT_BEFORE_OUTPUT)
            ->appendAll(ocConfig(array('EVENTS', 'error', 'beforeOutput'), array()));

        $this->event(self::EVENT_OUTPUT)
            ->appendAll(ocConfig(array('EVENTS', 'error', 'output'), array(array($this, 'output'))));

        $this->event(self::EVENT_AFTER_OUTPUT)
            ->appendAll(ocConfig(array('EVENTS', 'error', 'afterOutput'), array()));
    }

    /**
     * 错误处理
     * @param \Exception|\ErrorException|\Error $exception
     * @param bool $lastError
     * @throws Exception
     */
    public function exceptionHandle($exception, $lastError = false)
    {
        return $this->handle($exception, $lastError);
    }

    /**
     * 程序错误
     * @param string $level
     * @param string $message
     * @param string $file
     * @param string $line
     * @param string $context
     * @param bool $lastError
     * @return bool
     * @throws Exception
     */
    public function errorHandle($level, $message, $file, $line, $context = '', $lastError = false)
    {
        $errorException = new ErrorException($message, $level, $level, $file, $line);
        $exceptErrors = ocForceArray(ocConfig(array('ERROR_HANDLER', 'except_error_list'), array()));

        if (!in_array($level, $exceptErrors)) {
            $handler = new static();
            $handler->exceptionHandle($errorException, $lastError);
        }

        return false;
    }

    /**
     * @param \Exception|\ErrorException|\Error $exception
     * @param bool $lastError
     * @throws Exception
     */
    public function handle($exception, $lastError = false)
    {
        $error = ocGetExceptionData($exception, $lastError);
        $response = ocService('response', true);

        $response->clear();
        $response->setStatusCode(Response::STATUS_SERVER_ERROR);

        try {
            $this->fire(self::EVENT_REPORT, array($error));
            $this->fire(self::EVENT_BEFORE_OUTPUT, array($error));
            $result = $this->fire(self::EVENT_OUTPUT, array($error));
            if (ocEmpty($response->getBody())) {
                if (ocEmpty($result)) {
                    $this->output($error);
                } else {
                    $response->setBody($result);
                }
            }
            $this->fire(self::EVENT_AFTER_OUTPUT, array($error));
        } catch (\Exception $exception) {
            $this->output($error);
        } catch (\Error $error) {
            $this->output($error);
        }

        $response->send();
    }

    /**
     * 设置内容响应返回格式
     * @param string $responseFormat
     */
    public function setResponseFormat($responseFormat)
    {
        $this->responseFormat = $responseFormat;
    }

    /**
     * 输出错误
     * @param array $error
     * @param object $event
     * @param object $object
     * @throws Exception
     */
    public function output($error, $event = null, $object = null)
    {
        $responseFormat = $this->responseFormat ?: ocConfig('DEFAULT_RESPONSE_FORMAT', null);
        $isAPi = $responseFormat == self::RESPONSE_FORMAT_API;

        if (!$isAPi) {
            if (ocService('request', true)->isAjax()) {
                $isAPi = true;
            }
        }

        if ($isAPi) {
            $this->apiError($error);
        } else {
            $response = ocService('response', true);
            $contentType = $response->getHeaderOption('contentType');
            if (!$contentType) {
                $response->setContentType(ocConfig('DEFAULT_PAGE_CONTENT_TYPE', 'html'));
            }
            $defaultOutput = ocConfig(array('SYSTEM_SINGLETON_SERVICE_BINDS', 'errorOutput'));
            ocService('errorOutput', $defaultOutput)->display($error);
        }
    }

    /**
     * 错误报告
     * @param array $error
     * @param object $event
     * @param object $object
     * @throws Exception
     */
    public function report($error, $event, $object)
    {
        ocService('log', true)
            ->error($error['message'] . PHP_EOL . Log::getTraceString($error['traceInfo']));
    }

    /**
     * Api处理
     * @param array $error
     * @throws Exception
     */
    protected function apiError($error)
    {
        $response = ocService('response', true);
        $message = ocService()->api->getResult(OC_EMPTY, $error, 'error');
        $contentType = ocService()->response->getHeaderOption('contentType');

        if (!$contentType) {
            $contentType = ocConfig('DEFAULT_API_CONTENT_TYPE', 'json');
            $response->setContentType($contentType);
        }

        $content = ocService('api', true)->format($message, $contentType);
        $response->setStatusCode(Response::STATUS_SERVER_ERROR);
        $response->setBody($content, true);
    }
}