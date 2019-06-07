<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   控制器提供器基类Base
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Controllers\Provider;

use Ocara\Core\ServiceProvider;

defined('OC_PATH') or exit('Forbidden!');

class Base extends ServiceProvider
{
    protected $models = array();
    protected $apiContentType;
    protected $message;
    protected $hasRender = false;

    /**
     * 设置Ajax返回的数据格式
     * @param $contentType
     */
    public function setApiContentType($contentType)
    {
        $this->apiContentType = $contentType;
    }

    /**
     * 是否在HTTP头部返回错误码
     * @param bool $value
     */
    public function isSendApiErrorCode($value)
    {
        $value = $value ? 1 : 0;
        $this->config->set('API.is_send_error_code', $value);
    }

    /**
     * 获取返回信息
     * @return mixed
     */
    public function getMessage()
    {
        $message = $this->message;

        if (is_array($message)) {
            list($text, $params) = $message;
            $message = $this->lang->get($text, $params);
        } else {
            if (!ocEmpty($message)){
                $message = $this->lang->get($message);
            }
        }

        return $message;
    }
}