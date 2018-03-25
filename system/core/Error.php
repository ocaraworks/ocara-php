<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  错误处理类Error
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

use Ocara\Exception\Exception;
use Ocara\Ocara;

defined('OC_PATH') or exit('Forbidden!');

class Error extends Base
{

	/**
	 * 单例模式
	 */
	private static $_instance = null;

	private function __clone(){}
	private function __construct(){}

	public static function getInstance()
	{
		if (self::$_instance === null) {
			self::$_instance = new self();
			if ($callback = ocConfig('EVENT.error.write_log', array(Ocara::services()->log, 'error'), true)) {
				self::$_instance->event('writeLog')->append($callback);
			}
			if ($callback = ocConfig('EVENT.error.output', null)) {
				self::$_instance->event('output')->append($callback);
			}
			if ($callback = ocConfig('EVENT.error.ajax_output', null)) {
				self::$_instance->event('ajaxOutput')->append($callback);
			}
		}
		return self::$_instance;
	}

	/**
	 * 处理出错结果
	 * @param string $error
	 * @param array $params
	 * @param bool $required
	 * @return null
	 * @throws Exception
	 */
	public static function check($error, array $params = array(), $required = false)
	{
		if ($required == false) return null;
		self::show($error, $params);
	}

	/**
	 * 记录异常错误日志
	 * @param string $error
	 * @param array $params
	 */
	public static function writeLog($error, array $params = array())
	{
		try {
			$error = Ocara::services()->lang->get($error, $params);
			throw new Exception($error['message'], $error['code']);
		} catch(Exception $exception) {
			self::$_instance->event('writeLog')->fire(
				$exception->getMessage(), $exception->getTrace()
			);
		}
	}

	/**
	 * 显示异常错误
	 * @param string $error
	 * @param array $params
	 * @throws Exception
	 */
	public static function show($error, array $params = array())
	{
	    $services = Ocara::services();
        $services->transaction->rollback();

		if (!is_array($error)) {
			$error = $services->lang->get($error, $params);
		}

		if ($services->request->isAjax()) {
			try {
				throw new Exception($error['message'], $error['code']);
			} catch(Exception $exception) {
				ocExceptionHandler($exception);
			}
		} else {
			throw new Exception($error['message'], $error['code']);
		}
	}

	/**
	 * 抛出程序错误
	 * @param string $error
	 * @param array $params
	 * @param integer $errorType
	 */
	public static function trigger($error, array $params = array(), $errorType = E_USER_ERROR)
	{
		$errorType = $errorType ? : E_USER_ERROR;
		$error = Ocara::services()->lang->get($error, $params);
		trigger_error($error['message'], $errorType);
	}

	/**
	 * 手动异常处理
	 * @param object $exception
	 */
	public static function exceptionHandler($exception)
	{
		$function = ocConfig(
			'ERROR_HANDLER.exception_error',
			'ocExceptionHandler',
			true
		);
		if (function_exists($function)) {
			call_user_func($function, $exception);
		}
	}

	/**
	 * 错误处理
	 * @param $exception
	 */
	public function handler($exception)
	{
		$params = array($exception->getMessage(), $exception->getTrace());
		self::$_instance->event('writeLog')->fire($params);

		$params = ocGetExceptionData($exception);
		if (self::$_instance->event('output')->get()) {
			self::$_instance->event('output')->fire(array($params));
		}

		self::output($params);
	}

	/**
	 * 输出错误信息
	 * @param array $error
	 */
    public static function output($error)
	{
        $services = Ocara::services();
        $response = $services->response;
		if (!$response->getOption('statusCode')) {
            $response->setStatusCode(Response::STATUS_SERVER_ERROR);
		}

		if ($services->request->isAjax()) {
            if (self::$_instance->event('ajaxOutput')->get()) {
                self::$_instance->event('ajaxOutput')->fire(array($error));
            } else {
                $message = array();
                $message['code'] = $error['code'];
                $message['message'] = $error['message'];
                $services->ajax->show('error', $message);
            }
		} else {
            $services->errorOutput->display($error);
        }

        die();
	}
}