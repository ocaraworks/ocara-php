<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 事件处理器类EventHandler
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Basis;
use Ocara\Interfaces\Event as EventInterface;
use Ocara\Interfaces\Middleware;

class Event extends Basis implements EventInterface
{
    protected $_name;
    protected $_running;
    protected $_registry;
    protected $_defaultHandler;

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
     * 设置默认处理器
     * @param $callback
     * @return $this
     */
    public function setDefault($callback)
    {
        $this->_defaultHandler = $callback;
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
            ocService()->error->show('invalid_middleware');
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

        $count = count($this->_properties);
        $this->_properties[$count] = array(
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
            $this->_properties[$name] = $callback;
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
            if (isset($this->_properties[$name])) {
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
            $this->_properties[$key]['priority'] = $priority;
        }

        return $this;
    }

    /**
     * 删除事件处理器
     * @param $name
     * @return $this|array|bool
     */
    public function delete($name)
    {
        $key = $this->_getKey($name);
        if (is_integer($key)) {
            ocDel($this->_properties, $key);
        }

        return $this;
    }

    /**
     * 获取事件处理器
     * @param string $name
     * @return array|mixed
     */
    public function get($name = null)
    {
        if (isset($name)) {
            $key = $this->_getKey($name);
            if (is_integer($key)) {
                return $this->_properties[$name];
            }
            return null;
        }

        return $this->_properties;
    }

    /**
     * 检测是否存在事件处理器
     * @param $name
     * @return mixed
     */
    public function has($name = null)
    {
        if (isset($name)) {
            $key = $this->_getKey($name);
            return is_integer($key);
        }

        return !empty($this->_properties);
    }

    /**
     * 清空事件处理器
     * @return $this
     */
    public function clear()
    {
        $this->_properties = array();
        return $this;
    }

    /**
     * 触发事件
     * @param object $eventObject
     * @param array $params
     * @return mixed
     */
    public function trigger($eventObject, array $params = array())
    {
        $params = array_merge($params, array($this, $eventObject));
        $results = array();

        if ($this->_properties) {
            $handlers = $this->_properties;
            array_multisort(array_column(
                $handlers, 'priority'), SORT_DESC,
                array_column($handlers, 'index'), SORT_ASC,
                $handlers
            );

            $this->_running = true;
            foreach ($handlers as $key => $row) {
                $callback = $row['callback'];
                if ($this->_running) {
                    $results[$key] = $this->_runCallback($callback, $params);
                }
            }
        } elseif ($this->_defaultHandler) {
            $this->_running = true;
            $results[] = $this->_runCallback($this->_defaultHandler, $params);
        }

        $this->stop();
        return $results;
    }

    /**
     * 运行回调函数
     * @param $callback
     * @param $params
     * @return mixed
     */
    public function _runCallback($callback, $params)
    {
        if (is_object($callback)) {
            $callback = array($callback, 'handler');
        }

        return call_user_func_array($callback, $params);
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