<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   普通视图类View
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\View;

use Ocara\Ajax;
use Ocara\ViewBase;
use Ocara\Interfaces\View as ViewInterfaces;

defined('OC_PATH') or exit('Forbidden!');

class Rest extends ViewBase implements ViewInterfaces
{
    /**
     * 初始化
     */
    public function init()
    {
        return $this;
    }

    public function output($data)
    {
        Ocara::services()->response->setContentType($data['contentType']);
        Ajax::show('success', $data['message'], $data['data']);
    }
}