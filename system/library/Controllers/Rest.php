<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   普通控制器类Rest
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Controllers;

use Ocara\Core\ControllerBase;

class Rest extends ControllerBase
{
    protected static $controllerType = 'Rest';
    protected $isApi = true;
}