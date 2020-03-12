<?php

namespace Ocara\Service\Interfaces\Model;

interface Session
{
    /**
     * 读取session
     * @param $sessionId
     * @return null
     */
    public function read($sessionId);

    /**
     * 写入session
     * @param $data
     */
    public function write($data);

    /**
     * 删除session
     * @param $sessionId
     */
    public function destroy($sessionId);

    /**
     * 清理过期session
     */
    public function gc();
}