<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 事件处理器类EventHandler
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

use Ocara\Interfaces\Event as EventInterface;
use Ocara\Interfaces\Middleware;

class Event extends Basis implements EventInterface
{
    protected $_name;
    protected $_handlers;
    protected $_running;
    protected $_registry;

    /**
     * 添加事件处理器
     * @param $callback
     * @param int $args
     * @return $this
     */
    public function append($callback, $args = 0)
    {
        if ($callback) {
            call_user_func_array(array(&$this, '_create'), func_get_args());
        }

        return $this;
    }

    /**
     * 设置名称
     * @param $name
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * 新建事件处理器
     * @param $callback
     * @param int $args
     */
    protected function _create($callback, $args = 0)
    {
        if (is_string($callback)
            && preg_match('/^[\w\\\\]+$/', $callback)
            && class_exists($callback)
        ) {
            $callback = new $callback();
        }

        if (is_object($callback) && !($callback instanceof Middleware)) {
            Error::show('invalid_middleware');
        }

        $params = func_get_args();
        $name = null;
        $priority = 0;

        if (isset($params[2])) {
            $name = $args;
            $priority = (integer)$params[2];
        } else {
            if (is_string($args)) {
                $name = $args;
            } else {
                $priority = $args;
            }
        }

        $count = count($this->_handlers);
        $this->_handlers[$count] = array(
            'callback' => $callback,
            'index' => $count,
            'priority' => $priority,
        );

        if ($name) {
            $this->_registry[$name] = $count;
        }
    }

    /**
     * 修改事件处理器
     * @param $name
     * @param $callback
     * @return $this
     */
    public function modify($name, $callback)
    {
        $key = $this->_getKey($name);
        if (is_integer($key)) {
            $this->_handlers[$name] = $callback;
        }

        return $this;
    }

    /**
     * 获取KEY
     * @param $name
     * @return int|null
     */
    protected function _getKey($name)
    {
        $key = null;

        if (is_string($name)) {
            if (isset($this->_registry[$name])) {
                $key = $this->_registry[$name];
            }
        } elseif(is_integer($name)) {
            $name = $name - 1;
            if (isset($this->_handlers[$name])) {
                $key = $name;
            }
        }

        return $key;
    }

    /**
     * 修改事件处事理器的优先级
     * @param $name
     * @param $priority
     * @return $this
     */
    public function setPriority($name, $priority)
    {
        $key = $this->_getKey($name);
        if (is_integer($key)) {
            $this->_handlers[$key]['priority'] = $priority;
        }

        return $this;
    }

    /**
     * 删除事件处理器
     * @param $name
     * @return array|bool
     */
    public function remove($name)
    {
        $key = $this->_getKey($name);
        if (is_integer($key)) {
            ocDel($this->_handlers, $key);
        }

        return $this;
    }

    /**
     * 获取事件处理器
     * @param $name
     * @return mixed
     */
    public function get($name = null)
    {
        if (!isset($name)) {
            return $this->_handlers;
        }

        $key = $this->_getKey($name);
        if (is_integer($key)) {
            return $this->_handlers[$name];
        }

        return null;
    }

    /**
     * 检测是否存在事件处理器
     * @param $name
     * @return mixed
     */
    public function has($name)
    {
        $key = $this->_getKey($name);
        return is_integer($key);
    }

    /**
     * 清空事件处理器
     */
    public function clear()
    {
        $this->_handlers = array();
        return $this;
    }

    /**
     * 触发事件
     * @param $params
     * @return mixed
     */
    public function fire(array $params = array())
    {
        $handlers = $this->_handlers;

        if ($handlers) {
            array_multisort(array_column(
                $handlers, 'priority'), SORT_DESC,
                array_column($handlers, 'index'), SORT_ASC,
                $handlers
            );

            $params[] = $this;
            $this->_running = true;

            foreach ($this->_handlers as $row) {
                $callback = $row['callback'];
                if ($this->_running) {
                    if (is_object($callback)) {
                        $callback = array($callback, 'handler');
                    }
                    return Call::run($callback, $params);
                }
            }
        }
    }

    /**
     * 停止事件
     */
    public function stop()
    {
        $this->_running = false;
        return $this;
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