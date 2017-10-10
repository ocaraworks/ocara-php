<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 事件处理器类EventHandler
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

class EventHandler extends Basis
{
    protected $_handlers;
    protected $_running;

    /**
     * 附加事件处理器
     * @param $name
     * @param $callback
     * @param int $priority
     */
    public function append($name, $callback, $priority = 0)
    {
        if (!isset($this->_handlers[$name])) {
            $this->_handlers[$name] = array();
        }

        $priority = $priority ? (integer)$priority : 0;
        $index = count($this->_handlers);

        $this->_handlers[$name][] = array(
            'callback' => $callback,
            'index' => $index,
            'priority' => $priority,
        );
    }

    /**
     * 删除事件处理器
     * @param $name
     * @return array|bool
     */
    public function remove($name)
    {
        return !empty($this->_handlers[$name]) ? ocDel($this->_handlers, $name) : true;
    }

    /**
     * 获取事件处理器
     * @param $name
     * @return mixed
     */
    public function get($name)
    {
        return !empty($this->_handlers[$name]) ? $this->_handlers[$name] : array();
    }

    /**
     * 检测是否存在事件处理器
     * @param $name
     * @return mixed
     */
    public function has($name)
    {
        return isset($this->_handlers[$name]);
    }

    /**
     * 清空事件处理器
     */
    public function clear()
    {
        $this->_handlers = array();
    }

    /**
     * 触发事件
     * @param $data
     * @return mixed
     */
    public function trigger(array $data = array())
    {
        $handlers = $this->_handlers;
        array_multisort(array_column(
            $handlers, 'priority'), SORT_DESC,
            array_column($handlers, 'index'), SORT_ASC,
            $handlers
        );

        $this->setProperty($data);
        $params = array($this);

        $this->_running = true;
        foreach ($this->_handlers as $callback) {
            if ($this->_running) {
                call_user_func_array($callback, $params);
            }
        }
    }

    /**
     * 停止事件
     */
    public function stop()
    {
        $this->_running = false;
    }

    /**
     * 是否运行中
     * @return mixed
     */
    public function isRunning()
    {
        return $this->_running;
    }
}