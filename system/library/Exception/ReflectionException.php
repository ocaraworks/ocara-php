<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Reflection异常处理类OcaraReflectionException
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Exception;
use \ReflectionException as ReflectionExceptionBase;
use Ocara\Interfaces\Exception as ExceptionInterface;

defined('OC_PATH') or exit('Forbidden!');

class ReflectionException extends ReflectionExceptionBase implements ExceptionInterface
{
}