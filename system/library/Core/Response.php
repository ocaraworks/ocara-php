<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   HTTP响应类Response
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Core;

use Ocara\Core\Base;
use \Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class Response extends Base
{
    const STATUS_OK = 200;
    const STATUS_CREATED = 201;
    const STATUS_NO_CONTENT = 204;
    const STATUS_MOVED = 301;
    const STATUS_BAD_REQUEST = 400;
    const STATUS_UNAUTHORIZED = 401;
    const STATUS_FORBIDDEN = 403;
    const STATUS_NOT_FOUND = 404;
    const STATUS_NOT_ALLOWED = 405;
    const STATUS_GONE = 410;
    const STATUS_UNSUPPORTED_TYPE = 415;
    const STATUS_SERVER_ERROR = 500;
    const STATUS_NOT_IMPLEMENTED = 501;
    const STATUS_SERVICE_UNAVAILABLE = 503;

    protected $headers = array();
    protected $isSent = false;
    protected $lastHeaders = array();
    protected $body;

    /**
     * 发送头部信息
     * @param array $data
     */
    public function sendHeaders(array $data = array())
    {
        if (!headers_sent()) {
            if (empty($data)) {
                $data = $this->prepareHeaders();
            }

            $this->lastHeaders = $data;

            foreach ($data as $key => $header) {
                if (is_string($key)) {
                    $method = 'get' . ucfirst($key);
                    if (method_exists($this, $method)) {
                        $data[$key] = $this->$method();
                    } else {
                        $data[$key] = $key . ':' . $header;
                    }
                }
                header($data[$key]);
            }
        }
    }

    /**
     * 发送响应数据
     * @param bool $sendHeaders
     * @return bool
     */
    public function send($sendHeaders = true)
    {
        if ($this->isSent()) {
            return false;
        }

        if ($sendHeaders) {
            $this->sendHeaders();
        }

        echo $this->body;
        $this->isSent = true;
    }

    /**
     * 是否已发送
     * @return bool
     */
    public function isSent()
    {
        return $this->isSent;
    }

    /**
     * 设置响应体
     * @param $body
     */
    public function setBody($body)
    {
        if (!$this->body) {
            $this->body = $body;
        }
    }

    /**
     * 获取响应体
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * 获取最后响应头
     * @return array
     */
    public function getLastHeaders()
    {
        return $this->lastHeaders;
    }

    /**
     * 设置头部信息
     * @param string|array $headers
     */
    public function setHeader($headers)
    {
        $this->headers = array_merge($this->headers, (array)$headers);
    }

    /**
     * 设置头部选项
     * @param $name
     * @param null $value
     */
    public function setOption($name, $value = null)
    {
        $this->headers[$name] = $value;
    }

    /**
     * 移除头部信息项
     * @param $name
     */
    public function deleteOption($name)
    {
        ocDel($this->headers, $name);
    }

    /**
     * 移除已发送头部
     * @param $name
     */
    public function remove($name)
    {
        header_remove($name);;
    }

    /**
     * 设置状态
     * @param $code
     * @throws Exception
     */
    public function setStatusCode($code)
    {
        $httpStatus = ocConfig('HTTP_STATUS');
        if (array_key_exists($code, $httpStatus)) {
            $this->headers['statusCode'] = $code;
        }
    }

    /**
     * 设置响应文档类型
     * @param string $contentType
     */
    public function setContentType($contentType)
    {
        $this->headers['contentType'] = $contentType;
    }

    /**
     * 设置响应文档编码
     * @param string $charset
     */
    public function setCharset($charset = 'utf-8')
    {
        $this->headers['charset'] = $charset;
    }

    /**
     * 转移到另一个控制器动作
     * @param $route
     * @param array $params
     * @param null $moduleNamespace
     */
    public function transfer($route, array $params = array(), $moduleNamespace = null)
    {
        if ($route) {
            ocService()->app->run($route, $params, $moduleNamespace);
        } else {
            ocService('error', true)->show('not_null', array('route'));
        }
    }

    /**
     * 跳转到另一个控制器动作
     * @param $route
     * @param array $params
     */
    public function jump($route, array $params = array())
    {
        return $this->redirect(ocUrl($route, $params));
    }

    /**
     * 打开外部URL链接
     * @param $url
     */
    public function redirect($url)
    {
        if ($url) {
            if (!headers_sent()) {
                $this->remove('Location');
                $this->setOption('Location', $url);
                $this->send();
            }
        } else {
            ocService('error', true)->show('not_null', array('url'));
        }
    }

    /**
     * 停止
     */
    public function stop()
    {
        $this->isSent = true;
    }

    /**
     * 打开
     */
    public function open()
    {
        $this->isSent = false;
    }

    /**
     * 获取要发送的头数据
     * @return array
     */
    public function prepareHeaders()
    {
        $data = $this->headers;
        if ($statusCode = $this->getHeaderOption('statusCode')) {
            $data['statusCode'] = $statusCode;
        }
        $data['contentType'] = $this->getHeaderOption('contentType');
        return $data;
    }

    /**
     * 获取设置选项
     * @param $name
     * @return null
     */
    public function getHeaderOption($name)
    {
        if (isset($this->headers[$name])) {
            return $this->headers[$name];
        }

        return null;
    }

    /**
     * 返回状态码
     */
    public function getStatusCode()
    {
        $result = null;

        if (isset($this->headers['statusCode'])) {
            $httpStatus = ocConfig('HTTP_STATUS');
            if (isset($httpStatus[$this->headers['statusCode']])) {
                $result = $httpStatus[$this->headers['statusCode']];
            }
        }

        return $result;
    }

    /**
     * 获取返回内容类型
     */
    public function getContentType()
    {
        $result = null;

        if (isset($this->headers['contentType'])) {
            $contentType = strtolower($this->headers['contentType']);
            $mineTypes = ocConfig('MINE_TYPES');
            if (array_key_exists($contentType, $mineTypes)) {
                $contentType = $mineTypes[$contentType];
            }

            if (!empty($this->headers['charset'])) {
                $charset = $this->headers['charset'];
            } else {
                $charset = 'utf-8';
            }

            $result = "Content-Type:{$contentType}; charset={$charset}";
        }

        return $result;
    }
}