<?php
/**
 * Ocara开源框架 程序错误异常类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Exceptions;

use \ErrorException as ErrorExceptionBase;
use Ocara\Interfaces\Exception as ExceptionInterface;

defined('OC_PATH') or exit('Forbidden!');

class ErrorException extends ErrorExceptionBase implements ExceptionInterface
{
}