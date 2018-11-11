<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   普通视图类View
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Views;

use Ocara\Core\ViewBase;
use Ocara\Interfaces\View as ViewInterfaces;

defined('OC_PATH') or exit('Forbidden!');

class Api extends ViewBase implements ViewInterfaces
{
    public function output($data)
    {
        ocService()->response->setContentType($data['contentType']);
        ocService()->ajax->render('success', $data['message'], $data['data']);
    }
}