<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Ajax视图类View
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\View;

use Ocara\Interfaces\View as ViewInterfaces;
use Ocara\ViewBase;

defined('OC_PATH') or exit('Forbidden!');

class Rest extends ViewBase implements ViewInterfaces
{
    /**
     * 初始化
     */
    public function initialize()
    {
        return $this;
    }
}