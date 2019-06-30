<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   视图基类View
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Base;
use Ocara\Exceptions\Exception;
use Ocara\Service\Interfaces\Template as TemplateInterface;
use Ocara\Service\Xml;

defined('OC_PATH') or exit('Forbidden!');

class ViewBase extends Base
{
    /**
     * 获取路由
     * @param null $name
     * @return array|mixed|null
     */
    public function getRoute($name = null)
    {
        $route = $this->getVar('route');

        if (func_get_args()) {
            return isset($route[$name]) ? $route[$name] : null;
        }

        return $route;
    }

    /**
     * 输出内容
     * @param $content
     */
    public function outputApi($content)
    {
        $response = ocService()->response;
        $response->setBody($content);
    }

    /**
     * 渲染结果
     * @param |null $result
     * @return mixed|void|null
     * @throws Exception
     */
    public function renderApi($result)
    {
        $response = ocService()->response;

        if ($callback = ocConfig(array('SOURCE', 'api', 'return_result'), null)) {
            $result = call_user_func_array($callback, array($result));
        }

        $statusCode = $response->getOption('statusCode');
        if (!$statusCode && !ocConfig(array('API', 'is_send_error_code'), 0)) {
            $response->setStatusCode(Response::STATUS_OK);
            $result['statusCode'] = $response->getOption('statusCode');
        }

        $contentType = $response->getOption('contentType');

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

        $response->setBody($content);
        return $content;
    }

    /**
     * 获取XML结果
     * @param $result
     * @return mixed
     * @throws Exception
     */
    private function getXmlResult($result)
    {
        $xmlObj = new Xml();
        $xmlObj->setData('array', array('root', $result));
        $xml = $xmlObj->getContent();

        return $xml;
    }
}