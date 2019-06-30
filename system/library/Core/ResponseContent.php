<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   AJAX请求处理类Ajax
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Base;
use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class ResponseContent extends Base
{
    /**
     * 获取XML结果
     * @param $result
     * @return mixed
     */
    private function getXmlResult($result)
    {
        $xmlObj = new Xml();
        $xmlObj->setData('array', array('root', $result));
        $xml = $xmlObj->getContent();

        return $xml;
    }

    /**
     * 格式化响应内容
     * @param $result
     * @param $contentType
     * @return false|mixed|string
     */
	public function format($result, $contentType)
	{
        switch ($contentType)
        {
            case 'json':
                $content = json_encode($result);
                break;
            case 'xml':
                $content = $this->getXmlResult($result);
                break;
            default:
                $content = $result;
        }

        return $content;
	}
}