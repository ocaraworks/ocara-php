<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   普通视图类View
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Views;

use Ocara\Ocara;
use Ocara\ViewBase;
use Ocara\Interfaces\View as ViewInterfaces;

defined('OC_PATH') or exit('Forbidden!');

class Rest extends ViewBase implements ViewInterfaces
{
    public function output($data)
    {
        Ocara::services()->response->setContentType($data['contentType']);
        Ocara::services()->ajax->show('success', $data['message'], $data['data']);
    }
}