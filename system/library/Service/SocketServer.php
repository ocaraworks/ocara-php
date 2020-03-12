<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Socket服务插件SocketServer
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Service;

use Ocara\Exceptions\Exception;

class SocketServer extends SocketBase
{
    /**
     * 启动服务端
     * @param $host
     * @param $port
     * @param int $timeout
     * @return resource
     * @throws Exception
     */
    public function start($host, $port, $timeout = 0)
    {
        if ($timeout) @set_time_limit($timeout);

        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if ($this->socket === false) {
            $this->error = socket_strerror();
            $this->showError('failed_connect');
        } else {
            $this->host = gethostbyname($host);
            $this->port = $port;
            if (!@socket_bind($this->socket, $this->host, $this->port)) {
                $this->showSocketError('bind', 'conn');
            }
            if (!@socket_listen($this->socket)) {
                $this->showSocketError('listen', 'conn');
            }
            return $this->socket;
        }
    }

    /**
     * 接收客户端请求
     * @return bool|resource
     * @throws Exception
     */
    public function accept()
    {
        if (empty($this->socket)) {
            $this->showError('no_connect');
        }

        $this->conn = @socket_accept($this->socket);

        if ($this->conn === false) {
            $this->showSocketError('accept', null);
        }

        return $this->conn;
    }

    /**
     * 读取数据
     * @param int $length
     * @param int $type
     * @return string
     * @throws Exception
     */
    public function read($length = 512, $type = PHP_BINARY_READ)
    {
        if (empty($this->conn)) {
            $this->showError('no_accept_connect');
        }

        $result = @socket_read($this->conn, $length, $type);

        if ($result === false) {
            $this->showSocketError('read');
        }

        return $result;
    }

    /**
     * 发送数据
     * @param $content
     * @param int $length
     * @return int
     * @throws Exception
     */
    public function send($content, $length = 0)
    {
        if (empty($this->conn)) {
            $this->showError('no_accept_connect');
        }

        $length = $length ?: strlen($content);
        $result = @socket_write($this->conn, $content, $length);

        if ($result === false) {
            $this->showSocketError('write');
        }

        return $result;
    }

    /**
     * 关闭连接
     */
    public function close()
    {
        if (is_resource($this->conn)) {
            socket_close($this->conn);
        }
    }
}
