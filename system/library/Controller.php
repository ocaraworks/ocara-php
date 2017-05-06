<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   应用控制器基类Controller
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;
use Ocara\Service\Pager;
use Ocara\Interfaces\Controller as ControllerInterface;

defined('OC_PATH') or exit('Forbidden!');

class Controller extends ControllerBase implements ControllerInterface
{
	/**
	 * @var $_isSubmit 是否POST提交
	 * @var $_checkForm 是否检测表单
	 */
	private $_isSubmit = null;
	private $_submitMethod = 'post';
	private $_checkForm = true;

	private $_forms = array();

	/**
	 * 初始化设置
	 * @param array $route
	 */
	public function initialize(array $route)
	{
		$this->setRoute($route);
		$featureClass = Ocara::getControllerFeatureClass($this);
		$this->feature = new $featureClass();

		self::$container
			 ->bindSingleton('view', array($this->feature, 'getView'), array($this->getRoute()))
			 ->bindSingleton('formToken', array($this->feature, 'getFormToken'))
			 ->bindSingleton('validator', array($this->feature, 'getValidator'))
			 ->bindSingleton('db', Database::getInstance('default'))
			 ->bindSingleton('pager', array($featureClass, 'getPager'));

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
			$this->doClassAction($display);
		} else {
			$this->$actionMethod();
		}
		method_exists($this, '_after') && $this->_after();
	}

	/**
	 * 执行动作（类方法）
	 * @param $display
	 */
	public function doClassAction($display)
	{
		method_exists($this, '_action') && $this->_action();
		method_exists($this, '_form') && $this->_form();
		$this->_checkForm();

		if (Request::isAjax()) {
			method_exists($this, '_ajax') && $this->_ajax();
		} elseif ($this->_isSubmit && method_exists($this, '_submit')) {
			$this->_submit();
			$this->formToken->clear();
		} else{
			method_exists($this, '_display') && $this->_display();
			$display && $this->display();
		}
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
	 * 设置和获取表单提交方式
	 * @param string $method
	 */
	public function submitMethod($method = null)
	{
		if (func_num_args()) {
			$method = $method == 'get' ? 'get' : 'post';
			$this->_submitMethod = $method;
		}
		return $this->_submitMethod;
	}

	/**
	 * 设置和获取是否表单提交
	 */
	public function isSubmit()
	{
		if ($this->_isSubmit === null) {
			$this->_isSubmit = false;
			if (method_exists($this, '_isSubmit')) {
				$this->_isSubmit = $this->_isSubmit() ? true : false;
			} else {
				if ($this->_submitMethod == 'post') {
					$this->_isSubmit = Request::isPost();
				}
			}
		}
		return $this->_isSubmit;
	}

	/**
	 * 获取表单提交的数据
	 * @param string $key
	 * @param string $default
	 */
	public function getSubmit($key = null, $default = null)
	{
		$data = $this->_submitMethod == 'post' ? $_POST : $_GET;
		$data = Request::getRequestValue($data, $key, $default);
		return $data;
	}

	/**
	 * Ajax返回数据
	 * @param string $data
	 * @param string $message
	 * @param bool $type
	 */
	public function ajaxReturn($data = '', $message = '', $type = false)
	{
		if (is_array($message)) {
			list($text, $params) = $message;
			$message = Lang::get($text, $params);
		} else {
			$message = Lang::get($message);
		}

		Ajax::show('success', $message, $data, $type);
		method_exists($this, '_after') && $this->_after();
		die();
	}

	/**
	 * 打印模板
	 * @param string $file
	 * @param array $vars
	 */
	public function display($file = false, array $vars = array())
	{
		self::$container->response->setContentType('html');
		echo $this->render($file, $vars);
		method_exists($this, '_after') && $this->_after();
		die();
	}

	/**
	 * 渲染模板
	 * @param bool $file
	 * @param array $vars
	 * @return mixed
	 */
	public function render($file = false, array $vars = array())
	{
		$tokenTag  = $this->formToken->getTokenTag();

		foreach ($this->_forms as $formName => $form) {
			if (is_object($form) && $form instanceof Form) {
				$this->formToken->setRoute($form->getRoute());
				$token = $this->formToken->setToken($formName);
				$form->setToken($tokenTag, $token);
			}
		}

		if (empty($file)) {
			$tpl = $this->view->getTpl();
			if (empty($tpl)) {
				$this->view->setTpl($this->getRoute('action'));
			}
		}

		return $this->view->render($file, $vars, false);
	}

	/**
	 * 获取表单并自动验证
	 * @param string $name
	 */
	public function form($name = null)
	{
		if (empty($name)) {
			$name  = $this->getRoute('controller');
			$model = self::model();
			if (is_object($model) && $model instanceof Model) {
				$table = $model->getTable();
			} else {
				$table = $name;
			}
			if ($this->db->tableExists($table, false)) {
				$form = $this->form($name)->model($table, false);
			} else {
				Error::show('no_form');
			}
		} elseif (isset($this->_forms[$name])
			&& is_object($obj = $this->_forms[$name])
			&& $obj instanceof Form
		) {
			$form = $this->_forms[$name];
		} else {
			$this->_forms[$name]= $form = new Form();
			$form->initialize($name);
			$form->setRoute($this->getRoute());
			$this->view->assign($name, $form);
		}

		return $form;
	}

	/**
	 * 获取所有表单对象
	 */
	public function getForms()
	{
		return $this->_forms;
	}

	/**
	 * 开启/关闭/检测表单验证功能
	 * @param bool|null $check
	 */
	public function checkForm($check = null)
	{
		if ($check === null) {
			return $this->_checkForm;
		}
		$this->_checkForm = $check ? true : false;
	}

	/**
	 * 表单检测
	 */
	protected function _checkForm()
	{
		$this->isSubmit();
		if (!($this->_isSubmit && $this->_checkForm && $this->_forms))
			return true;

		$tokenTag  = $this->formToken->getTokenTag();
		$postToken = $this->getSubmit($tokenTag);
		$postForm  = null;

		if (empty($postToken)) {
			$this->_showCheckFormError('failed_validate_token');
		}

		foreach ($this->_forms as $formName => $form) {
			$this->formToken->setRoute($form->getRoute());
			if ($this->formToken->exists($formName, $postToken)) {
				$postForm = $form;
				$this->formToken->setCurrentForm($formName);
				break;
			}
		}

		if ($postForm === null) {
			$this->_showCheckFormError('not_exists_form');
		}

		if ($postForm->validateForm()) {
			$data = $this->getSubmit();
			if (!$postForm->validate($this->validator, $data)) {
				$this->_showCheckFormError(
					'failed_validate_form',
					array($this->validator->getError()),
					$this->validator->getErrorSource()
				);
			}
		}

		return true;
	}

	/**
	 * 显示表单检测错误
	 * @param string $errorType
	 * @param array $params
	 * @param array $data
	 */
	private function _showCheckFormError($errorType, $params = array(), $data = array())
	{
		$error['errorType'] = $errorType;
		$error['errorInfo'] = Lang::get($errorType, $params);;
		$error['errorData'] = $data;

		$callback = ocConfig(array('CALLBACK', 'form', 'check_error'), false);
		if ($callback) {
			Call::run($callback, array($error, $this->getRoute()));
		} else {
			Error::show($error['errorInfo']);
		}

		die();
	}
}
