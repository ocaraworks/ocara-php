<?php
/**
 * Socket插件基类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Service;

use Ocara\Core\ServiceBase;
use Ocara\Exceptions\Exception;

class SocketBase extends ServiceBase
{
    public $host;
    public $port;
    public $socket;
    public $conn;
    public $error;

    /**
     * 析构函数
     */
    public function __construct()
    {
        ocCheckExtension('sockets');
    }

    /**
     * 显示错误
     * @param string $errorType
     * @param string $connId
     * @throws Exception
     */
    protected function showSocketError($errorType, $connId = 'socket')
    {
        $this->error = $connId ? socket_last_error($this->$connId) : socket_last_error();

        if (preg_match('/^[\x{4e00}-\x{9fa5}A-Za-z0-9_]+$/u', $this->error)) {
            $errorMsg = iconv('gbk', 'utf-8', socket_strerror($this->error));
        } else {
            $errorMsg = $this->error;
        }

        $params = array('errorType' => $errorType, $this->error, $errorMsg);
        $this->showError('socket_error', $params, 'Socket');
    }
}
