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

class Controller extends Base implements ControllerInterface
{
	/**
	 * @var $_controllerType 控制器类型
	 * @var $_ajaxContentType AJAX返回文档类型
	 * @var $provider 控制器提供者
	 */
	public $provider;

	/**
	 * 初始化设置
	 * @param array $route
	 */
	public function init(array $route)
	{
		Config::set('CALLBACK.ajax.return_result', array($this, 'formatAjaxResult'));

		$controllerType = Route::getControllerType($route['module'], $route['controller']);
		$provider = 'Ocara\Controller\Provider\\' . $controllerType;
		$this->provider = new $provider(compact('route'));
		$this->provider->init();
		$this->provider->bindEvents($this);

		method_exists($this, '_start') && $this->_start();
		method_exists($this, '_module') && $this->_module();
		method_exists($this, '_control') && $this->_control();
	}

	/**
	 * 执行动作
	 * @param string $actionMethod
	 */
	public function doAction($actionMethod)
	{
		$doWay = $this->provider->getDoWay();

		if (!$this->provider->isSubmit()) {
			if (method_exists($this, '_isSubmit')) {
				$this->provider->isSubmit($this->_isSubmit());
			} elseif ($this->submitMethod() == 'post') {
				$this->provider->isSubmit(Request::isPost());
			}
		}

		if ($doWay == 'common') {
			$this->doCommonAction();
		} elseif($doWay == 'ajax') {
			$this->doAjaxAction();
		}
	}

	/**
	 * 执行动作（类方法）
	 */
	public function doCommonAction()
	{
		method_exists($this, '_action') && $this->_action();
		method_exists($this, '_form') && $this->_form();
		$this->checkForm();

		if (Request::isAjax()) {
			$data = OC_EMPTY;
			if (method_exists($this, '_ajax')) {
				$data = $this->_ajax();
			}
			$this->provider->ajaxReturn($data);
		} elseif ($this->provider->isSubmit() && method_exists($this, '_submit')) {
			$this->_submit();
			$this->provider->formManager->clearToken();
		} else{
			method_exists($this, '_display') && $this->_display();
			$this->provider->display();
		}
	}

	/**
	 * 执行动作
	 * @param string $actionMethod
	 */
	public function doAjaxAction($actionMethod)
	{
		if ($actionMethod == '_action') {
			$result = $this->_action();
		} else {
			$result = $this->$actionMethod();
		}

		$this->provider->display($result);
	}

	/**
	 * 执行动作（返回值）
	 * @param string $method
	 * @param array $params
	 * @return mixed
	 * @throws \Ocara\Exception
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
		if ($instance = $this->provider->getService($key)) {
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
		if (is_object($this->provider) && method_exists($this->provider, $name)) {
			return call_user_func_array(array(&$this->provider, $name), $params);
		}
		parent::_call($name, $params);
	}
}
