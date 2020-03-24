<?php
/**
 * API视图类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Views;

use Ocara\Core\Response;
use Ocara\Core\ViewBase;
use Ocara\Exceptions\Exception;
use Ocara\Interfaces\View as ViewInterfaces;

class Api extends ViewBase implements ViewInterfaces
{
    /**
     * 输出内容
     * @param $content
     */
    public function output($content)
    {
        ocService()->response->setBody($content);
    }

    /**
     * 渲染API结果
     * @param $result
     * @return mixed
     * @throws Exception
     */
    public function render($result)
    {
        $contentType = ocService()->response->getHeaderOption('contentType');

        if (!$contentType) {
            $contentType = ocConfig('DEFAULT_API_CONTENT_TYPE', 'json');
            ocService()->response->setContentType($contentType);
        }

        $content = ocService()->api->format($result, $contentType);
        return $content;
    }
}