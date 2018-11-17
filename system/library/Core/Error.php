<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  错误处理类Error
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Exceptions\Exception;
use Ocara\Core\ServiceProvider;

defined('OC_PATH') or exit('Forbidden!');

class Error extends ServiceProvider
{
    const EVENT_WRITE_LOG = 'writeLog';

	/**
	 * 处理出错结果
	 * @param string $error
	 * @param array $params
	 * @param bool $required
	 * @return null
	 * @throws Exception
	 */
	public function check($error, array $params = array(), $required = false)
	{
		if ($required == false) return null;
		$this->show($error, $params);
	}

	/**
	 * 记录异常错误日志
	 * @param string $error
	 * @param array $params
	 */
	public function writeLog($error, array $params = array())
	{
		try {
			$error = ocService()->lang->get($error, $params);
			throw new Exception($error['message'], $error['code']);
		} catch(Exception $exception) {
            $this->event(self::EVENT_WRITE_LOG)->fire(
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
	public function show($error, array $params = array())
	{
        ocService('transaction', true)->rollback();

		if (!is_array($error)) {
			$error = ocService('lang', true)->get($error, $params);
		}

        throw new Exception($error['message'], $error['code']);
	}

	/**
	 * 抛出程序错误
	 * @param string $error
	 * @param array $params
	 * @param integer $errorType
	 */
	public function trigger($error, array $params = array(), $errorType = E_USER_ERROR)
	{
		$errorType = $errorType ? : E_USER_ERROR;
		$error = ocService()->lang->get($error, $params);
		trigger_error($error['message'], $errorType);
	}

    /**
     * 魔术方法-调用未定义的方法时
     * @param string $name
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public function __call($name, $params)
    {
        if ($this->hasProperty($name)) {
            if ($params && !is_array($params[0])) {
                $params[0] = ocService()->lang->get($params[0], $params);
            }
            $exceptionClass = $this->getProperty($name);
            throw ocClass($exceptionClass, $params);
        }

        return parent::__call($name, $params);
    }
}