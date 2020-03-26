<?php
/**
 * 验证器类接口
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Interfaces;

interface Validate
{
    /**
     * 验证处理
     * @param $value
     * @param mixed $args
     * @return mixed
     */
    public function handle($value, $args = null);
}