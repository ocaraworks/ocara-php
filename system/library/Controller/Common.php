<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   应用控制器基类Controller
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Controller;
use Ocara\Interfaces\Controller as ControllerInterface;
use Ocara\Ocara;
use Ocara\Config;
use Ocara\Request;
use Ocara\Response;
use Ocara\Error;
use Ocara\Form;
use Ocara\Call;
use Ocara\Lang;
use Ocara\Database;
use Ocara\ControllerBase;
use Ocara\ModelBase;

defined('OC_PATH') or exit('Forbidden!');

class Common extends ControllerBase implements ControllerInterface
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
		Config::set('CALLBACK.ajax_return', array($this, 'formatAjaxResult'));

		$featureClass = Ocara::getControllerFeatureClass($this);
		$this->feature = new $featureClass();

		self::$container
			 ->bindSingleton('view', array($this->feature, 'getView'), array($this->getRoute()))
			 ->bindSingleton('formToken', array($this->feature, 'getFormToken'))
			 ->bindSingleton('validator', array($this->feature, 'getValidator'))
			 ->bindSingleton('db', function(){ Database::create('default'); })
			 ->bindSingleton('pager', array($this->feature, 'getPager'));

		$this->session->initialize();

		method_exists($this, '_start')   && $this->_start();
		method_exists($this, '_module')  && $this->_module();
		method_exists($this, '_control') && $this->_control();
	}

	/**
	 * 执行动作
	 * @param string $actionMethod
	 */
	public function doAction($actionMethod)
	{
		if ($actionMethod == '_action') {
			$this->doClassAction();
		} else {
			$this->$actionMethod();
		}
		method_exists($this, '_after') && $this->_after();
	}

	/**
	 * 执行动作（类方法）
	 */
	public function doClassAction()
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
			$this->display();
		}
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
	 * 设置和获取表单提交方式
	 * @param null $method
	 * @return string
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
	 * @param null $key
	 * @param null $default
	 * @return array|null|string
	 */
	public function getSubmit($key = null, $default = null)
	{
		$data = $this->_submitMethod == 'post' ? $_POST : $_GET;
		$data = Request::getRequestValue($data, $key, $default);
		return $data;
	}

	/**
	 * 打印模板
	 * @param bool $file
	 * @param array $vars
	 */
	public function display($file = false, array $vars = array())
	{
		$content = $this->render($file, $vars);
		$this->view->output(array('content' => $content));
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
	 * @param null $name
	 * @return $this|Form
	 * @throws \Ocara\Exception
	 */
	public function form($name = null)
	{
		if (empty($name)) {
			$name  = $this->getRoute('controller');
			$model = $this->model();
			if (is_object($model) && $model instanceof ModelBase) {
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
	 * @param null $check
	 * @return bool
	 */
	public function checkForm($check = null)
	{
		if ($check === null) {
			return $this->_checkForm;
		}
		$this->_checkForm = $check ? true : false;
	}

	/**
	 * 设置AJAX返回格式（回调函数）
	 * @param $result
	 */
	public function formatAjaxResult($result)
	{
		if ($result['status'] == 'success') {
			$this->response->setStatusCode(Response::STATUS_OK);
			return $result;
		} else {
			if (!$this->response->getHeader('statusCode')) {
				$this->response->setStatusCode(Response::STATUS_SERVER_ERROR);
			}
			return $result;
		}
	}

	/**
	 * 数据模型字段验证
	 * @param $data
	 * @param $class
	 * @param Validator|null $validator
	 * @return mixed
	 */
	public function validate($data, $class, Validator &$validator = null)
	{
		$validator = $validator ? $validator : $this->validator;
		return $validator->validate($data, $class);
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
