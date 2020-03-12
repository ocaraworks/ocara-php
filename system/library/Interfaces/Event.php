<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 事件处理器类EventHandler
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Interfaces;

interface Event
{
    /**
     * 添加事件处理器
     * @param $callback
     * @param int $args
     * @return $this
     */
    public function append($callback, $args = 0);

    /**
     * 设置名称
     * @param $name
     */
    public function setName($name);

    /**
     * 修改事件处理器
     * @param $name
     * @param $callback
     * @return $this
     */
    public function modify($name, $callback);

    /**
     * 修改事件处事理器的优先级
     * @param $name
     * @param $priority
     * @return $this
     */
    public function setPriority($name, $priority);

    /**
     * 删除事件处理器
     * @param $name
     * @return array|bool
     */
    public function delete($name);

    /**
     * 获取事件处理器
     * @param string $name
     * @return array|mixed
     */
    public function get($name = null);

    /**
     * 检测是否存在事件处理器
     * @param $name
     * @return mixed
     */
    public function has($name);

    /**
     * 清空事件处理器
     */
    public function clear();

    /**
     * 触发事件
     * @param object $targetObject
     * @param array $params
     * @return mixed
     */
    public function trigger($targetObject, array $params = array());

    /**
     * 停止事件
     */
    public function stop();

    /**
     * 是否运行中
     * @return mixed
     */
    public function isRunning();
}