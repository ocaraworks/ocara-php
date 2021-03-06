<?php
/**
 * 事件处理器类接口
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Interfaces;

interface Event
{
    /**
     * 添加事件处理器
     * @param $callback
     * @param string $name
     * @param int $priority
     * @return $this|EventInterface
     */
    public function append($callback, $name = null, $priority = 0);

    /**
     * 批量绑定事件处理器
     * @param array $callbackList
     * @param $groupName
     * @param $priority
     * @return $this
     */
    public function appendAll(array $callbackList, $groupName = null, $priority = 0);

    /**
     * 设置名称
     * @param string $name
     */
    public function setName($name);

    /**
     * 修改事件处理器
     * @param string $name
     * @param mixed $callback
     * @return $this
     */
    public function modify($name, $callback);

    /**
     * 修改事件处事理器的优先级
     * @param string $name
     * @param int $priority
     * @return $this
     */
    public function setPriority($name, $priority);

    /**
     * 删除事件处理器
     * @param string $name
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
     * @param string $name
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