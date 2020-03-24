<?php
/**
 * 全局变量类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

class Globals extends Base
{
    protected $data;

    /**
     * 设置属性
     * @param string $name
     * @param mixed $value
     */
    public function set($name, $value = null)
    {
        $this->data[$name] = $value;
    }

    /**
     * 获取属性
     * @param null $name
     * @return null
     */
    public function get($name = null)
    {
        return array_key_exists($name, $this->data) ? $this->data[$name] : null;
    }

    /**
     * 检查键名是否存在
     * @param string $name
     * @return bool
     */
    public function has($name = null)
    {
        return array_key_exists($name, $this->data);
    }

    /**
     * 删除属性
     * @param $name
     * @return mixed
     */
    public function delete($name)
    {
        if (array_key_exists($name, $this->data)) {
            $this->data[$name] = null;
            unset($this->data[$name]);
        }
    }

    /**
     * 清理属性
     */
    public function clear()
    {
        $this->data = array();
    }
}