<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Restful控制器基类RestController
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Controller;
use Ocara\Ocara;
use Ocara\Config;
use Ocara\Request;
use Ocara\Response;
use Ocara\Error;
use Ocara\Lang;
use Ocara\Database;
use Ocara\ControllerBase;
use Ocara\Ajax;

defined('OC_PATH') or exit('Forbidden!');

class Rest extends ControllerBase
{
	/**
	 * @var $_message 返回消息
	 */
	protected $_message;
	protected $_hypermediaLink;

	/**
	 * 初始化设置
	 * @param array $route
	 */
	public function initialize(array $route)
	{
		Request::setAjax();
		$this->setRoute($route);
		Config::set('CALLBACK.ajax_return', array($this, 'formatAjaxResult'));

		$defaultContentType = ocConfig('CONTROLLERS.rest.content_type','json');
		$featureClass = Ocara::getControllerFeatureClass($this);
		$this->feature = new $featureClass();

		self::$container->response->setContentType($defaultContentType);
		self::$container
			 ->bindSingleton('view', array($this->feature, 'getView'), array($this->getRoute()))
			 ->bindSingleton('validator', array($this->feature, 'getValidator'))
			 ->bindSingleton('db', function(){ Database::factory('default'); })
			 ->bindSingleton('pager', array($this->feature, 'getPager'));

		$this->session->initialize();

		method_exists($this, '_start')   && $this->_start();
		method_exists($this, '_module')  && $this->_module();
		method_exists($this, '_control') && $this->_control();
	}

	/**
	 * 执行动作
	 * @param string $actionMethod
	 * @param bool $display
	 */
	public function doAction($actionMethod, $display = true)
	{
		if ($actionMethod == '_action') {
			$result = $this->_action();
		} else {
			$result = $this->$actionMethod();
		}

		if (method_exists($this, '_after')) {
			$this->_after();
		}

		$this->display($result, $this->_message, $this->_ajaxContentType);
	}

	/**
	 * 执行动作（返回值）
	 * @param string $method
	 * @param array $params
	 */
	public function doReturnAction($method, array $params = array())
	{
		if (method_exists($this, $method)) {
			return call_user_func_array(array($this, $method), $params);
		} else {
			Error::show('no_action_return');
		}
	}

	/**
	 * 设置返回消息
	 * @param $message
	 */
	public function setMessage($message)
	{
		$this->_message = $message;
	}

	/**
	 * 设置Hypermedia
	 * @param $linkInfo
	 */
	public function setMediaLink(array $linkInfo)
	{
		$this->_hypermediaLink = $linkInfo;
	}

	/**
	 * Ajax返回数据
	 * @param string $data
	 * @param string $message
	 * @param bool $contentType
	 */
	public function display($data = '', $message = '', $contentType = false)
	{
		if (is_array($message)) {
			list($text, $params) = $message;
			$message = Lang::get($text, $params);
		} else {
			$message = Lang::get($message);
		}

		$this->view->output(compact('contentType', 'message', 'data'));
		method_exists($this, '_after') && $this->_after();
		die();
	}

	/**
	 * 获取当前请求的ID
	 * @return null|string
	 */
	public function getRequestId()
	{
		return Request::getGet(ocConfig('CONTROLLERS.rest.id_param', 'id'));
	}

	/**
	 * 输出内容（回调函数）
	 * @param $result
	 */
	public function formatAjaxResult($result)
	{
		if ($result['status'] == 'success') {
			$successCode = strtr(
				$this->getRoute('action'),
				ocConfig('CONTROLLERS.rest.success_code_map')
			);
			$this->response->setStatusCode($successCode);
			return $result['body'];
		} else {
			if (!$this->response->getHeader('statusCode')) {
				$this->response->setStatusCode(Response::STATUS_SERVER_ERROR);
			}
			return $result;
		}
	}
}
