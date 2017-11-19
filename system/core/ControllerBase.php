<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   应用控制器基类Controller
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;
use Ocara\Interfaces\Controller as ControllerInterface;

defined('OC_PATH') or exit('Forbidden!');

class ControllerBase extends Base implements ControllerInterface
{
	/**
	 * @var $feature 控制特性类
	 * @var $_contentType AJAX返回文档类型
	 * @var $_models 模型类
	 */
	public $feature;
	public $service;

	protected $_ajaxContentType;
	private $_models = array();

	/**
	 * 初始化设置
	 * @param array $route
	 */
	public function init(array $route)
	{
	}

	/**
	 * 执行动作
	 * @param string $actionMethod
	 */
	public function doAction($actionMethod)
	{}

	/**
	 * 执行动作（返回值）
	 * @param string $method
	 * @param array $params
	 */
	public function doReturnAction($method, array $params = array())
	{}

	/**
	 * 是否在HTTP头部返回错误码
	 * @param bool $value
	 */
	public function setReturnAjaxHeaderErrorCode($value)
	{
		$value = $value ? 1 : 0;
		Config::set('AJAX.return_header_error_code', $value);
	}

	/**
	 * Ajax返回数据
	 * @param string $data
	 * @param string $message
	 */
	public function ajaxReturn($data = '', $message = '')
	{
		if (is_array($message)) {
			list($text, $params) = $message;
			$message = Lang::get($text, $params);
		} else {
			$message = Lang::get($message);
		}

		$contentType = $this->_ajaxContentType;
		if ($this->_ajaxContentType) {
			$contentType = ocConfig('DEFAULT_AJAX_CONTENT_TYPE', 'json');
		}

		$this->response->setContentType($contentType);
		$this->view->ajaxOutput($data, $message);
		method_exists($this, '_after') && $this->_after();

		die();
	}

	/**
	 * 获取或设置Model-静态属性保存
	 * @param string $class
	 */
	public function model($class = null)
	{
		if (empty($class)) {
			$class = '\Model\\' . ucfirst($this->getRoute('controller'));
		}

		if (isset($this->_models[$class])) {
			$model = $this->_models[$class];
			if (is_object($model) && $model instanceof ModelBase) {
				return $model;
			}
		}

		$this->_models[$class] = new $class();
		return $this->_models[$class];
	}

	/**
	 * 获取不存在的属性时
	 * @param string $key
	 * @return array|null
	 */
	public function &__get($key)
	{
		if ($this->hasProperty($key)) {
			$value = &$this->getProperty($key);
			return $value;
		}
		if ($instance = $this->service->getService($key)) {
			return $instance;
		}
		Error::show('no_property', array($key));
	}

	/**
	 * 调用未定义的方法时
	 * @param string $name
	 * @param array $params
	 * @return mixed
	 * @throws Exception\Exception
	 */
	public function __call($name, $params)
	{
		if (is_object($this->view) && method_exists($this->view, $name)) {
			return call_user_func_array(array(&$this->view, $name), $params);
		}
		parent::_call($name, $params);
	}
}
