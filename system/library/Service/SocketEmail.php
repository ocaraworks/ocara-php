<?php
/**
 * Socket邮件发送插件类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Service;

use Ocara\Exceptions\Exception;
use Ocara\Core\ServiceBase;

class SocketEmail extends ServiceBase
{
    public $lastResult;
    public $fo;
    public $sender;
    public $username;
    public $password;
    public $lastCommand;

    /**
     * 析构函数
     * SocketEmail constructor.
     * @param string $sender
     * @param string $host
     * @param int $port
     * @param string $username
     * @param string $password
     * @param int $timeout
     * @throws Exception
     */
    public function __construct($sender, $host, $port, $username, $password, $timeout = 20)
    {
        $port = $port ?: 25;

        $this->fo = @fsockopen(gethostbyname($host), $port, $errNo, $errMsg, $timeout);

        if (empty($this->fo)) {
            $errMsg = iconv('gbk', 'utf-8', $errMsg);
            ocService()->error->show('failed_email_socket_connect', array($errNo, $errMsg));
        }

        if ($timeout) {
            @socket_set_timeout($this->fo, $timeout);
        }

        $this->sender = $sender;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * 发送邮件
     * @param string $receiver
     * @param string $header
     * @param string $content
     * @return bool
     * @throws Exception
     */
    public function send($receiver, $header, $content)
    {
        @socket_set_blocking($this->fo, 1);

        $this->lastResult = fgets($this->fo, 512);
        if (!preg_match('/^220/', $this->lastResult)) {
            $this->writeLog('Email send error on 220 validate.');
            return false;
        }

        if (!$this->putCmd("HELO 127.0.0.1", 250)) {
            $this->writeLog('Email send error on HELO.');
            return false;
        }

        if (!$this->putCmd("AUTH LOGIN " . base64_encode($this->username), 334)) {
            $this->writeLog('Email send error on AUTH LOGIN.');
            return false;
        }

        if (!$this->putCmd(base64_encode($this->password), 235)) {
            $this->writeLog('Email send error on password.');
            return false;
        }

        if (!$this->putCmd("MAIL FROM:<{$this->sender}>", 250)) {
            $this->writeLog('Email send error on MAIL FROM.');
            return false;
        }

        if (!$this->putCmd("RCPT TO:<{$receiver}>", 250)) {
            $this->writeLog('Email send error on AUTH RCPT TO.');
            return false;
        }

        if (!$this->putCmd("DATA", 354)) {
            $this->writeLog('Email send error on AUTH DATA.');
            return false;
        }

        $content = $content . "\r\n.\r\n";
        fputs($this->fo, $header . "\r\n" . $content);

        if (!$this->putCmd("QUIT", 250)) {
            $this->writeLog('Email send error on QUIT.');
            return false;
        }

        fclose($this->fo);
        return true;
    }

    /**
     * 写日志
     * @param $message
     * @throws Exception
     */
    public function writeLog($message)
    {
        ocService()->log->write($message . '|message: ' . $this->lastResult);
    }

    /**
     * 执行命令
     * @param string $command
     * @param string $errorStatus
     * @return bool
     */
    public function putCmd($command, $errorStatus)
    {
        $command = $command . "\r\n";
        $this->lastCommand = $command;

        @fputs($this->fo, $command);
        $this->lastResult = @fgets($this->fo, 1024);

        if (preg_match('/^' . $errorStatus . OC_DIR_SEP, $this->lastResult)) {
            return $this->lastResult;
        }

        @fclose($this->fo);
        return false;
    }
}
