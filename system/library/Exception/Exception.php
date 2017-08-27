<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   异常处理类Exception
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Exception;
use \Exception as ExceptionBase;
use Ocara\Interfaces\Exception as ExceptionInterface;

defined('OC_PATH') or exit('Forbidden!');

class Exception extends ExceptionBase implements ExceptionInterface
{
}