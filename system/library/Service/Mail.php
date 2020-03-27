<?php
/**
 * 邮件发送服务插件类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Service;

use Ocara\Core\ServiceBase;
use Ocara\Exceptions\Exception;

class Mail extends ServiceBase
{
    /**
     * @var $boundary 边界字符串
     */
    public $sender;
    public $receiver;
    public $host;
    public $port;

    private $username;
    private $password;

    public $cc;
    public $bcc;
    public $subject;
    public $content;
    public $header;
    public $replyTo;

    public $text;
    public $html;
    public $related;
    public $attachments;
    public $params;
    public $boundary;

    /**
     * Mail constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $sender = ocConfig(array('EMAIL', 'sender'));
        $host = ocConfig(array('EMAIL', 'host'));
        $port = ocConfig(array('EMAIL', 'port'));
        $username = ocConfig(array('EMAIL', 'username'));
        $password = ocConfig(array('EMAIL', 'password'));

        $this->setServer($host, $port, $username, $password);
        $this->setSender($sender);

        $this->boundary = $this->newBoundary(1);
    }

    /**
     * 设置SMTP服务器信息
     * @param string $host
     * @param string $port
     * @param string $username
     * @param string $password
     */
    public function setServer($host, $port, $username = null, $password = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * 设置发件人
     * @param string $sender
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
        $this->replyTo = $sender;
    }

    /**
     * 设置收件人和抄送人
     * @param string $receiver
     * @param string|array $cc
     * @param string|array $bcc
     */
    public function setReceiver($receiver, $cc = null, $bcc = null)
    {
        $cc = ocForceArray($cc);
        $bcc = ocForceArray($bcc);

        $this->cc = $this->packCc($cc);
        $this->bcc = $this->packBcc($bcc);
        $this->receiver = $receiver;
    }

    /**
     * 设置标题
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * 本机smtp方式发送邮件
     * @param string $header
     * @param array $params
     * @return bool
     */
    public function localSend($header = null, $params = null)
    {
        if ($this->host) @ini_set('SMTP', $this->host);
        if ($this->port) @ini_set('smtp_port', $this->port ?: 25);

        @ini_set('sendmail_from', $this->sender);

        $this->params = $params;
        $contentType = 'multipart/alternative';

        if ($this->related || $this->attachments) {
            $contentType = 'multipart/mixed';
        }

        $this->header = $header ?: $this->getHeader($contentType);
        $this->content = $this->getContent();

        return mail($this->receiver, $this->subject, $this->content, $this->header, $this->params);
    }

    /**
     * Socket发送邮件
     * @return bool
     * @throws Exception
     */
    public function socketSend()
    {
        $contentType = 'multipart/alternative';

        if ($this->related || $this->attachments) {
            $contentType = 'multipart/mixed';
        }

        $this->header = $this->getHeader($contentType);
        $this->content = $this->getContent();

        $socketEmailObj = new SocketEmail(
            $this->sender, $this->host, $this->port,
            $this->username, $this->password
        );

        return $socketEmailObj->send($this->receiver, $this->header, $this->content);
    }

    /**
     * 新建boundary边界符
     * @param integer $num
     * @return string
     */
    protected function newBoundary($num)
    {
        srand(time() * intval($num));
        return md5(mt_rand());
    }

    /**
     * 生成邮件头
     * @param string $contentType
     * @return string
     */
    protected function getHeader($contentType)
    {
        $header = "From: {$this->sender}<{$this->sender}>" . PHP_EOL;
        $header .= $this->replyTo ? "Reply-To: {$this->replyTo}" . PHP_EOL : false;
        $header .= "To: {$this->receiver}" . PHP_EOL;
        $header .= $this->cc . $this->bcc;
        $header .= "Subject: {$this->subject}" . PHP_EOL;
        $header .= "Mime-Version: 1.0" . PHP_EOL;
        $header .= "Content-Type: {$contentType}; boundary=\"{$this->boundary}\"" . PHP_EOL;

        return $header . "\r\n";
    }

    /**
     * 生成邮件体
     * @return string
     */
    protected function getContent()
    {
        $content = null;

        if ($this->related || $this->attachments) {
            $subBoundary = $this->newBoundary(2);
            $thirdSubBoundary = $this->newBoundary(3);

            $content .= "--{$this->boundary}" . PHP_EOL;
            $content .= "Content-type: multipart/related; type=\"multipart/alternative\"; boundary=\"{$subBoundary}\"" . PHP_EOL;
            $content .= PHP_EOL . "--{$subBoundary}" . PHP_EOL;
            $content .= "Content-type: multipart/alternative; boundary=\"{$thirdSubBoundary}\"" . PHP_EOL;
            $content .= $this->getText($thirdSubBoundary);
            $content .= PHP_EOL . "--{$subBoundary}--" . PHP_EOL;

            if ($this->attachments) {
                $content .= $this->attachments;
            }
            $content .= PHP_EOL;
        } else {
            $content .= $this->getText($this->boundary);
        }

        $content .= PHP_EOL . "--{$this->boundary}--" . PHP_EOL;

        return $content . PHP_EOL;
    }

    /**
     * 生成纯文本和HTML段
     * @param string $subBoundary
     * @return string
     */
    protected function getText($subBoundary)
    {
        $content = null;

        if ($this->text) {
            $content .= "--{$subBoundary}" . PHP_EOL;
            $content .= $this->text . PHP_EOL;
        }

        if ($this->html) {
            $content .= "--{$subBoundary}" . PHP_EOL;
            $content .= $this->html . PHP_EOL;
        }

        return PHP_EOL . $content . PHP_EOL;
    }

    /**
     * 组合抄送人
     * @param string $cc
     * @return bool|string
     */
    protected function packCc($cc = null)
    {
        if ($cc) {
            $this->cc = implode(',', $cc);
        }

        return $this->cc ? "Cc: {$this->cc}" . PHP_EOL : false;
    }

    /**
     * 组合秘密抄送人
     * @param array $bcc
     * @return bool|string
     */
    protected function packBcc(array $bcc = array())
    {
        if ($bcc) {
            $this->bcc = implode(',', $bcc);
        }

        return $this->bcc ? "Bcc: {$this->bcc}" . PHP_EOL : false;
    }

    /**
     * 设置回复地址
     * @param string $replyTo
     */
    public function setReplyTo($replyTo = null)
    {
        $this->replyTo = $replyTo ?: $this->sender;
    }

    /**
     * 不设置回复地址
     */
    public function noReply()
    {
        $this->replyTo = null;
    }

    /**
     * 设置纯文本
     * @param string $text
     * @param string $charset
     * @param string $encoding
     */
    public function setText($text, $charset = 'UTF-8', $encoding = 'quoted-printable')
    {
        $content = "Content-Type: text/plain; charset=\"{$charset}\"" . PHP_EOL;
        $content .= "Content-Transfer-Encoding: {$encoding}" . PHP_EOL;
        $content .= PHP_EOL . "{$text}" . PHP_EOL;

        $this->text = $content;
    }

    /**
     * 设置HTML内容
     * @param string $html
     * @param string $charset
     * @param string $encoding
     */
    public function setHtml($html, $charset = 'UTF-8', $encoding = 'quoted-printable')
    {
        $content = "Content-Type: text/html; charset=\"{$charset}\"" . PHP_EOL;
        $content .= "Content-Transfer-Encoding: {$encoding}" . PHP_EOL;
        $content .= PHP_EOL . "{$html}" . PHP_EOL;

        $this->html = $content . PHP_EOL;
    }

    /**
     * 设置内嵌资源（暂不支持）
     */
    public function setRelated()
    {
        $this->related = false;
    }

    /**
     * 设置附件
     * @param array|string $attachments
     * @return bool
     * @throws Exception
     */
    public function setAttachment($attachments = null)
    {
        $content = null;
        $attachments = ocForceArray($attachments);

        if (empty($attachments)) return false;

        foreach ($attachments as $file) {
            if (ocFileExists($file) && $fileContent = ocRead($file)) {
                $basename = ocBasename($file);
                $extName = strrchr($basename, '.');
                $content .= PHP_EOL . "--{$this->boundary}" . PHP_EOL;
                $content .= "Content-type:" . $this->getMimeType($extName) . ";name={$basename}" . PHP_EOL;
                $content .= "Content-Disposition: attachment;filename={$basename}" . PHP_EOL;
                $content .= "Content-Transfer-Encoding: base64" . PHP_EOL . PHP_EOL;
                $content .= chunk_split(base64_encode($fileContent)) . PHP_EOL;
            }
        }

        $this->attachments = $content . PHP_EOL;
    }

    /**
     * 获取附件MIME类型
     * @param $extName
     * @return string
     * @throws Exception
     */
    public function getMimeType($extName)
    {
        return ocConfig(array('MINE_TYPES', trim($extName, '.')));
    }
}
