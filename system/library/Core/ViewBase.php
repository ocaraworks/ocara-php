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
     * 输出内容
     * @param $content
     */
    public function outputApi($content)
    {
        ocService()->response->setBody($content);
    }

    /**
     * 渲染API结果
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
        $content = ocService()->api->format($result, $contentType);

        return $content;
    }
}