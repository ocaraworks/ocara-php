<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  错误处理类Error
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

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
	public static function log($error, array $params = array())
	{
		try {
			$error = Lang::get($error, $params);
			throw new Exception($error['message'], $error['code']);
		} catch(Exception $exception) {
			if ($callback = ocConfig('CALLBACK.error.write_log', null)) {
				Call::run($callback, array(ocGetExceptionData($exception)));
			}
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
		self::$container->transaction->rollback();

		if (!is_array($error)) {
			$error = Lang::get($error, $params);
		}

		if (Request::isAjax()) {
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
		$errorType = $errorType ? $errorType : E_USER_ERROR;
		$error = Lang::get($error, $params);
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
	 * 输出错误信息
	 * @param array $error
	 */
    public static function output($error)
	{
		if (!self::$container->response->getHeader('statusCode')) {
			self::$container->response->setStatusCode(Response::STATUS_SERVER_ERROR);
		}

		if (Request::isAjax()) {
			self::_ajaxOutput($error);
		}

		if ($error['type'] == 'program_error') {
			$displayError = @ini_get('display_errors');
			if (empty($displayError)) die();
		}

		if (function_exists('ocLang')) {
			$error['desc'] 	= ocLang($error['type']);
		} else {
			$error['desc'] 	= ucfirst($error['type']) . ': ';
		}

		$error['code']  = $error['code'] ? "[{$error['code']}]" : null;
		$error['class'] = $error['type'] == 'program_error' ? 'oc-error' : 'oc-exception';

		if (isset($traceInfo[0])) {
			$lastTrace = $traceInfo[0];
			$error['file'] = isset($lastTrace['file']) ? $lastTrace['file'] : $error['file'];
			$error['line'] = isset($lastTrace['line']) ? $lastTrace['line'] : $error['line'];
		}

		$error['file']  = trim(ocCommPath(self::_stripRootPath($error['file'])), OC_DIR_SEP);
		$error['trace'] = nl2br(ocCommPath($error['trace']));

		if (OC_PHP_SAPI == 'cli') {
			list ($trace, $traceInfo) = ocDel($error, 'trace', 'traceInfo');
			$error = array_merge(array('time' => date('Y-m-d H:i:s')), $error);
			$content = ocBr2nl(ocJsonEncode($error) . PHP_EOL . $trace);
		} else {
			$filePath = OC_SYS . 'modules/exception/index.php';
			self::$container->response->sendHeaders();
			if (ocFileExists($filePath)) {
				ob_start();
				include($filePath);
				$content = ob_get_contents();
				ob_end_clean();
			} else {
				$content = self::getSimpleTrace($error);
			}
		}

		echo $content;
		die();
	}

	/**
	 * 获取简洁的Trace内容
	 * @param $error
	 * @return string
	 */
	public static function getSimpleTrace($error)
	{
		return 'Lost exception template file.';
	}

	/**
	 * Ajax输出错误
	 * @param $error
	 */
	private static function _ajaxOutput($error)
	{
		if ($callback = ocConfig('CALLBACK.error.ajax_output', null)) {
			Call::run($callback, array($error));
		} else {
			$message = array();
			$message['code'] = $error['code'];
			$message['message'] = $error['message'];
			Ajax::show('error', $message);
		}

		die();
	}

	/**
	 * 去除当前出错文件路径的根目录
	 * @param string $errorFile
	 */
	private static function _stripRootPath($errorFile)
	{
		$filePath = ocCommPath(realpath($errorFile));
		$rootPath = ocCommPath(realpath(OC_ROOT));
		$ocPath   = ocCommPath(realpath(OC_PATH)) . OC_DIR_SEP;

		if (strpos($filePath, $ocPath) === 0) {
			$filePath = str_ireplace($ocPath, OC_EMPTY, $filePath);
		} elseif (strpos($filePath, $rootPath) === 0) {
			$filePath = str_ireplace(OC_ROOT, OC_EMPTY, $filePath);
		}

		return $filePath;
	}
}