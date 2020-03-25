<?php
/**
 * 事件处理类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use \Closure;
use \ReflectionException;
use Ocara\Exceptions\Exception;

use Ocara\Interfaces\Event as EventInterface;
use Ocara\Interfaces\Middleware as MiddlewareInterface;

class Event extends Basis implements EventInterface
{
    protected $name;
    protected $running;
    protected $registry;
    protected $defaultHandler;
    protected $isResource;
    protected $handlers = array();

    /**
     * 添加事件处理器
     * @param $callback
     * @param string $name
     * @param int $priority
     * @return $this|EventInterface|\Ocara\Interfaces\EventInterface
     * @throws Exception
     */
    public function append($callback, $name = null, $priority = 0)
    {
        if ($callback) {
            $this->create($callback, $name, $priority);
        }

        return $this;
    }

    /**
     * 批量绑定事件处理器
     * @param array $callbackList
     * @param string $groupName
     * @param int $priority
     * @return $this|EventInterface
     * @throws Exception
     */
    public function appendAll(array $callbackList, $groupName = null, $priority = 0)
    {
        if ($groupName) {
            $this->create($callbackList, $groupName, $priority, true);
        } else {
            foreach ($callbackList as $callback) {
                call_user_func_array(array(&$this, 'create'), array($callback));
            }
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
        $this->defaultHandler = $callback;
        return $this;
    }

    /**
     * 设置名称
     * @param $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param $callback
     * @return bool
     */
    public function isClass($callback)
    {
        return is_object($callback) && !$callback instanceof Closure;
    }

    /**
     * 是否资源事件
     * @param bool $isResource
     * @return $this
     */
    public function resource($isResource = true)
    {
        $this->isResource = !!$isResource;
        return $this;
    }

    /**
     * 新建事件处理器
     * @param $callback
     * @param string $name
     * @param int $priority
     * @param bool $isGroup
     * @throws Exception
     */
    protected function create($callback, $name = null, $priority = 0, $isGroup = false)
    {
        if ($isGroup && is_array($callback)) {
            array_walk($callback, array($this, 'checkCallback'));
        } else {
            $this->checkCallback($callback);
        }

        $name = $name ?: null;
        $priority = $priority ?: 0;

        $count = count($this->handlers);
        $this->handlers[$count] = array(
            'callback' => $callback,
            'index' => $count,
            'priority' => $priority,
            'is_group' => $isGroup
        );

        if ($name) {
            $this->registry[$name] = $count;
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
        $key = $this->getKey($name);
        if (is_integer($key)) {
            $this->handlers[$name] = $callback;
        }

        return $this;
    }

    /**
     * 获取KEY
     * @param $name
     * @return int|null
     */
    protected function getKey($name)
    {
        $key = null;

        if (is_string($name)) {
            if (isset($this->registry[$name])) {
                $key = $this->registry[$name];
            }
        } elseif (is_integer($name)) {
            $name = $name - 1;
            if (isset($this->handlers[$name])) {
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
        $key = $this->getKey($name);
        if (is_integer($key)) {
            $this->handlers[$key]['priority'] = $priority;
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
        $key = $this->getKey($name);
        if (is_integer($key)) {
            ocDel($this->handlers, $key);
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
            $key = $this->getKey($name);
            if (is_integer($key)) {
                return $this->handlers[$name];
            }
            return null;
        }

        return $this->handlers;
    }

    /**
     * 检测是否存在事件处理器
     * @param $name
     * @return mixed
     */
    public function has($name = null)
    {
        if (isset($name)) {
            $key = $this->getKey($name);
            return is_integer($key);
        }

        return !empty($this->handlers);
    }

    /**
     * 清空事件处理器
     * @return $this
     */
    public function clear()
    {
        $this->handlers = array();
        return $this;
    }

    /**
     * 触发事件
     * @param object $eventObject
     * @param array $params
     * @return array|mixed
     * @throws ReflectionException
     */
    public function trigger($eventObject, array $params = array())
    {
        $result = null;
        $handlerLength = 0;
        $params = array_merge($params, array($this, $eventObject));
        $results = array();

        if ($this->handlers) {
            $handlers = $this->handlers;
            $handlerLength = count($handlers);
            array_multisort(array_column(
                $handlers, 'priority'), SORT_DESC,
                array_column($handlers, 'index'), SORT_ASC,
                $handlers
            );

            $this->running = true;
            foreach ($handlers as $key => $row) {
                $callback = $row['callback'];
                if (is_array($callback) && $row['is_group']) {
                    $callbackResult = array();
                    foreach ($callback as $oneKey => $one) {
                        if ($this->canCallback($one)) {
                            $callbackResult[$oneKey] = $this->runCallback($one, $params);
                        }
                    }
                    if ($callbackResult) {
                        $results[$key] = $callbackResult;
                    }
                } else {
                    if ($this->canCallback($callback)) {
                        $results[$key] = $this->runCallback($callback, $params);
                    }
                }
            }
        } elseif ($this->defaultHandler) {
            $handlerLength = 2;
            $this->running = true;
            if ($this->canCallback($this->defaultHandler)) {
                $results[] = $this->runCallback($this->defaultHandler, $params);
            }
        }

        if ($handlerLength) {
            if ($handlerLength == 1) {
                $result = $results ? reset($results) : null;
            } else {
                $result = $results;
            }
        }

        $this->stop();
        return $result;
    }

    /**
     * 运行回调函数
     * @param $callback
     * @param $params
     * @return mixed
     */
    public function runCallback($callback, $params)
    {
        if ($this->isClass($callback)) {
            $callback = array($callback, 'handle');
        }

        return call_user_func_array($callback, $params);
    }

    /**
     * 回调检测
     * @param $callback
     * @param int $key
     * @return array
     * @throws \Ocara\Exceptions\Exception
     */
    public function checkCallback(&$callback, $key = 0)
    {
        if (is_string($callback)) {
            if (strstr($callback, OC_NS_SEP)) {
                $callback = new $callback();
            }
        } elseif (is_array($callback)) {
            if ($callback) {
                $class = array_shift($callback);
                if (is_object($class)) {
                    $object = $class;
                } else {
                    $object = new $class();
                }
                array_unshift($callback, $object);
            }
        }

        if ($this->isClass($callback)) {
            if (!$callback instanceof MiddlewareInterface) {
                ocService()->error->show('invalid_middleware');
            }
        }

        return $callback;
    }

    /**
     * 是否可回调
     * @param $callback
     * @return bool
     * @throws ReflectionException
     */
    public function canCallback($callback)
    {
        return $this->running && is_object($callback) || ocIsCallable($callback);
    }

    /**
     * 停止事件
     */
    public function stop()
    {
        $this->running = false;
        return $this;
    }

    /**
     * 是否运行中
     * @return mixed
     */
    public function isRunning()
    {
        return $this->running;
    }
}