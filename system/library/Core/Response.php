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

    protected $_headers = array();
    protected $_body;
    protected $_isSend;

    /**
     * 发送头部信息
     * @param array $data
     * @throws Exception
     */
	public function sendHeaders(array $data = array())
	{
		if (!headers_sent()) {
			if (empty($data)) {
				$data = $this->prepareHeaders();
			}

			foreach ($data as $key => $header) {
				if (is_string($key)) {
					$method = '_get' . ucfirst($key);
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
     * @param bool $stop
     */
	public function send($stop = false)
    {
        if (!$this->_isSend) {
            echo $this->_body;
            if ($stop){
                $this->_isSend = true;
            }
        }
    }

    /**
     * 是否已发送
     * @param $isSend
     */
    public function isSend($isSend)
    {
        $this->_isSend = $isSend ? true : false;
    }

    /**
     * 设置响应体
     * @param $body
     */
    public function setBody($body)
    {
        $this->_body = $body;
    }

    /**
     * 获取响应体
     * @return mixed
     */
    public function getBody()
    {
        return $this->_body;
    }

	/**
	 * 设置头部信息
	 * @param string|array $headers
	 */
	public function setHeader($headers)
	{
		$this->_headers = array_merge($this->_headers, (array)$headers);
	}

	/**
	 * 设置头部选项
	 * @param $name
	 * @param null $value
	 */
	public function setOption($name, $value = null)
	{
		$this->_headers[$name] = $value;
	}

    /**
     * 获取头部设置
     * @param string $name
     * @return null
     * @throws Exception
     */
	public function getOption($name)
	{
		$this->prepareHeaders();
		return $this->_getOption($name);
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
			$this->_headers['statusCode'] = $code;
		}
	}

	/**
	 * 设置响应文档类型
	 * @param string $contentType
	 */
	public function setContentType($contentType)
	{
		$this->_headers['contentType'] = $contentType;
	}

	/**
	 * 设置响应文档编码
	 * @param string $charset
	 */
	public function setCharset($charset = 'utf-8')
	{
		$this->_headers['charset'] = $charset;
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
     * @throws Exception
     */
    public function jump($route, array $params = array())
    {
        return $this->redirect(ocUrl($route, $params));
    }

    /**
     * 打开外部URL链接
     * @param $url
     * @param bool $die
     */
	public function redirect($url, $die = true)
	{
		if ($url) {
			if (!headers_sent()) {
				header_remove('location');
				header('location:' . $url);
			}
			$die && die();
		} else {
            ocService('error', true)->show('not_null', array('url'));
		}
	}

    /**
     * 获取要发送的头数据
     * @return array
     * @throws Exception
     */
	public function prepareHeaders()
	{
		$data = $this->_headers;
		if ($statusCode = $this->_getOption('statusCode')) {
			$data['statusCode'] = $statusCode;
		}

		if (empty($this->_headers['contentType'])) {
			if (ocService('request', true)->isAjax()) {
				$this->_headers['contentType'] = ocConfig('DEFAULT_AJAX_CONTENT_TYPE', 'json');
			} else {
				$this->_headers['contentType'] = ocConfig('DEFAULT_CONTENT_TYPE', 'html');
			}
		}

		$data['contentType'] = $this->_getOption('contentType');

		return $data;
	}

	/**
	 * 获取设置选项
	 * @param $name
	 * @return null
	 */
	public function _getOption($name)
	{
		if (isset($this->_headers[$name])) {
			return $this->_headers[$name];
		}

		return null;
	}

	/**
	 * 返回状态码
	 */
	public function _getStatusCode()
	{
		$result = null;

		if (isset($this->_headers['statusCode'])) {
			$httpStatus = ocConfig('HTTP_STATUS');
			if (isset($httpStatus[$this->_headers['statusCode']])) {
				$result = $httpStatus[$this->_headers['statusCode']];
			}
		}

		return $result;
	}

	/**
	 * 设置返回内容类型
	 */
	public function _getContentType()
	{
		$result = null;

		if (isset($this->_headers['contentType'])) {
			$contentType = strtolower($this->_headers['contentType']);
			$mineTypes = ocConfig('MINE_TYPES');
			if (array_key_exists($contentType, $mineTypes)) {
				$contentType = $mineTypes[$contentType];
			}

			if (!empty($this->_headers['charset'])) {
				$charset = $this->_headers['charset'];
			} else {
				$charset = 'utf-8';
			}

			$result = "Content-Type:{$contentType}; charset={$charset}";
		}

		return $result;
	}
}