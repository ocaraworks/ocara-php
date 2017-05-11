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

	protected $_ajaxContentType;

	private $_models = array();

	/**
	 * 初始化设置
	 * @param array $route
	 */
	public function initialize(array $route)
	{}

	/**
	 * 执行动作
	 * @param string $actionMethod
	 * @param bool $display
	 */
	public function doAction($actionMethod, $display = true)
	{}

	/**
	 * 执行动作（返回值）
	 * @param string $method
	 * @param array $params
	 */
	public function doReturnAction($method, array $params = array())
	{}

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
		Ajax::show('success', $message, $data);
		method_exists($this, '_after') && $this->_after();
		die();
	}

	/**
	 * 获取或设置Model-静态属性保存
	 * @param string $class
	 * @param bool $required
	 */
	public function model($class = null)
	{
		if (empty($class)) {
			$class = '\Model\\' . $this->getRoute('controller');
		}

		if (isset($this->_models[$class])) {
			$model = $this->_models[$class];
			if (is_object($model) && $model instanceof Model) {
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
			$value = $this->getProperty($key);
			return $value;
		}
		if (self::$container->exists($key)) {
			$instance = self::$container->get($key);
			return $instance;
		}
		Error::show('no_property', array($key));
	}

	/**
	 * 调用未定义的方法时
	 * @param string $name
	 * @param array $params
	 */
	public function __call($name, $params)
	{
		if (is_object($this->view) && method_exists($this->view, $name)) {
			return call_user_func_array(array(&$this->view, $name), $params);
		}
		Error::show('no_method', array($name));
	}
}
