<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 语言配置控制类Lang
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

class FormManager extends ServiceProvider
{
	protected $_forms = array();

	/**
	 * 注册服务
	 */
	public function register()
	{
		$validate = ocConfig('SERVICE.validate', '\Ocara\Service\Validate');
		$this->_container
			->bindSingleton('formToken', '\Ocara\FormToken')
			->bindSingleton('validator', '\Ocara\Validator', array($validate))
		;
	}

	/**
	 * 新建表单
	 * @param $name
	 * @return Form
	 */
	public function create($name)
	{
		$form = new Form($name);
		$form->setRoute($this->getRoute());
		$this->_forms[$name] = $form;

		return $form;
	}

	/**
	 * 获取提交的表单
	 * @return null
	 */
	public function getSubmitForm($postToken)
	{
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

		return $postForm;
	}

	/**
	 * 获取表单
	 * @param string $name
	 * @return array|bool
	 */
	public function getForm($name = null)
	{
		if (func_num_args()) {
			return $this->hasForm($name) ? $this->_forms[$name] : null;
		}

		return $this->_forms;
	}

	/**
	 * 是否存在表单
	 * @param $name
	 * @return bool
	 */
	public function hasForm($name)
	{
		return isset($this->_forms[$name])
			&& is_object($obj = $this->_forms[$name])
			&& $obj instanceof Form;
	}

	/**
	 * 验证表单
	 * @param $form
	 * @param $data
	 */
	public function validate($form, $data)
	{
		if ($form->validateForm()) {
			if (!$form->validate($this->validator, $data)) {
				$this->_showCheckFormError(
					'failed_validate_form',
					array($this->validator->getError()),
					$this->validator->getErrorSource()
				);
			}
		}
	}

	/**
	 * 设置Token
	 */
	public function setToken()
	{
		$tokenTag = $this->formToken->getTokenTag();

		foreach ($this->_forms as $formName => $form) {
			if (is_object($form) && $form instanceof Form) {
				$this->formToken->setRoute($form->getRoute());
				$token = $this->formToken->setToken($formName);
				$form->setToken($tokenTag, $token);
			}
		}
	}

	/**
	 * 清理Token
	 */
	public function clearToken()
	{
		$this->formToken->clear();
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