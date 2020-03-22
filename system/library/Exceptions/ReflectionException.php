<?php
/**
 * Reflection异常处理类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Exceptions;

use \ReflectionException as BaseReflectionException;
use Ocara\Interfaces\Exception as ExceptionInterface;

defined('OC_PATH') or exit('Forbidden!');

class ReflectionException extends BaseReflectionException implements ExceptionInterface
{
}