<?php
/**
 
 * Ocara开源框架 基类Base
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

defined('OC_PATH') or exit('Forbidden!');

abstract class BaseEntity extends Base
{
    /**
     * 加载数据
     * @param array $data
     * @param bool $initialize
     * @return $this
     */
    public function data(array $data, $initialize = false)
    {
        if ($data) {
            $this->setProperty($data);
        }
        return $this;
    }
}