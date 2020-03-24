<?php
/**
 * Session数据库处理接口
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Interfaces;

use Ocara\Core\Container;

interface SessionDatabase
{
    /**
     * 获取Session内容
     * @param $sessionId
     * @return string
     */
    public function read($sessionId);

    /**
     * 写入Session
     * @param $data
     * @return bool
     * @throws Exception
     */
    public function write($data);

    /**
     * 销毁Session
     * @param $sessionId
     * @return bool
     */
    public function destory($sessionId);

    /**
     * 垃圾回收
     * @throws Exception
     */
    public function clear();
}