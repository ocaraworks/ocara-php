<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   应用控制器基类Controller
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

use Ocara\ServiceProvider;
use Ocara\Interfaces\Controller as ControllerInterface;

defined('OC_PATH') or exit('Forbidden!');

class Controller extends serviceProvider implements ControllerInterface
{
	/**
	 * @var $_provider 控制器提供者
	 */
	protected $_provider;

	/**
	 * 初始化设置
	 */
	public function init()
	{
	    $route = ocService()->app->getRoute();
		$controllerType = Route::getControllerType($route['module'], $route['controller']);
		$provider = 'Ocara\Controllers\Provider\\' . $controllerType;
		$this->_provider = new $provider(compact('route'));
        $this->_provider->bindEvents($this);
        $this->_provider->boot();

		$this->config->set('SOURCE.ajax.return_result', array($this->_provider, 'formatAjaxResult'));

		method_exists($this, '_start') && $this->_start();
		method_exists($this, '_module') && $this->_module();
		method_exists($this, '_control') && $this->_control();
	}

	/**
	 * 获取当前的提供者
	 * @return 控制器提供者
	 */
	public function provider()
	{
		return $this->_provider;
	}

	/**
	 * 执行动作
	 * @param string $actionMethod
	 */
	public function doAction($actionMethod)
	{
		$doWay = $this->_provider->getDoWay();

		if (!$this->_provider->isSubmit()) {
			if (method_exists($this, '_isSubmit')) {
				$this->_provider->isSubmit($this->_isSubmit());
			} elseif ($this->submitMethod() == 'post') {
				$this->_provider->isSubmit($this->request->isPost());
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

		if ($this->request->isAjax()) {
			$data = OC_EMPTY;
			if (method_exists($this, '_ajax')) {
				$data = $this->_ajax();
			}
			$this->_provider->ajaxReturn($data);
		} elseif ($this->_provider->isSubmit() && method_exists($this, '_submit')) {
			$this->_submit();
			$this->_provider->formManager->clearToken();
		} else{
			method_exists($this, '_display') && $this->_display();
			$this->_provider->display();
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

		$this->_provider->display($result);
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

		if ($instance = $this->_provider->getService($key)) {
			return $instance;
		}

        ocService()->error->show('no_property', array($key));
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
        if (isset($this->_traits[$name])) {
            return call_user_func_array($this->_traits[$name], $params);
        }

        if (is_object($this->_provider)) {
            return call_user_func_array(array(&$this->_provider, $name), $params);
        }

        ocService()->error->show('no_method', array($name));
	}
}
