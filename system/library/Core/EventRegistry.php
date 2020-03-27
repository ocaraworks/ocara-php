<?php
/**
 * 事件注册局
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use Ocara\Exceptions\Exception;

class EventRegistry extends Base
{
    public $events = array();

    /**
     * 新建类的事件
     * @param string $class
     * @param string $eventName
     * @return array|mixed|object|void|null
     * @throws Exception
     */
    public function newEvent($class, $eventName)
    {
        $event = ocContainer()->create('event');
        $event->setName($eventName);
        return $this->events[$class][$eventName] = $event;
    }

    /**
     * 获取类的事件列表
     * @param string $class
     * @return array|mixed
     */
    public function getClassEvents($class)
    {
        return array_key_exists($class, $this->events) ? $this->events[$class] : array();
    }
}