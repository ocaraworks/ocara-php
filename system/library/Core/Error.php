<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  错误处理类Error
 * @Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Core;

use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class Error extends ServiceProvider
{
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
     * @param $error
     * @param array $params
     * @throws Exception
     */
    public function writeLog($error, array $params = array())
    {
        try {
            $error = ocService()->lang->get($error, $params);
            throw new Exception($error['message'], $error['code']);
        } catch (Exception $exception) {
            $error = ocGetExceptionData($exception);
            ocService('log', true)
                ->write($error['message'] . PHP_EOL . Log::getTraceString($error['traceInfo']));
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
        $errorType = $errorType ?: E_USER_ERROR;
        $error = ocService()->lang->get($error, $params);
        trigger_error($error['message'], $errorType);
    }
}