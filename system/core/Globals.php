<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 全局变量类Globals
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

use Ocara\Base;

defined('OC_PATH') or exit('Forbidden!');

class Globals extends Base
{
    /**
     * 设置属性
     * @param string $name
     * @param mixed $value
     */
    public function set($name, $value = null)
    {
        $this->setProperty($name, $value);
    }

    /**
     * 获取属性
     * @param string $name
     */
    public function get($name = null, $args = null)
    {
        return $this->getProperty($name, $args);
    }

    /**
     * 检查键名是否存在
     * @param string $name
     */
    public function has($name = null)
    {
        return $this->hasProperty($name);
    }

    /**
     * 删除属性
     * @param string $name
     */
    public function delete($name)
    {
        return $this->delProperty($name);
    }

    /**
     * 清理属性
     */
    public function clear()
    {
        $this->clearProperies();
    }
}