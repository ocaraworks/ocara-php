<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 语言配置控制类Lang
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Exceptions\Exception;
use \Ocara\Core\Form;

defined('OC_PATH') or exit('Forbidden!');

class FormManager extends ServiceProvider
{
    const EVENT_CHECK_ERROR = 'checkError';

    protected $_route;
    protected $_form;

    /**
     * 注册服务
     * @throws Exception
     */
	public function register()
	{
		$validator = ocConfig('SERVICE.validator', '\Ocara\Core\Validator');
		$validate = ocConfig('SERVICE.validate', '\Ocara\Service\Validate');

		$this->_container->bindSingleton('validator', $validator, array($validate));
	}

    /**
     * 注册事件
     * @throws \Ocara\Exceptions\Exception
     */
    public function registerEvents()
    {
        $this->event(self::EVENT_CHECK_ERROR)
             ->append(ocConfig(array('EVENT', 'form', 'check_error'), null));
    }

    /**
     * 新建表单
     * @param $formName
     * @return mixed
     * @throws Exception
     */
	public function create($formName)
	{
	    if (!$this->hasProperty($formName)) {
            $form = $this->createService('form', array($formName));
            $token = $this->formToken->generate($formName, $this->_route);

            $this->saveToken($formName, $token);
            $form->setTokenInfo(array($this->getTokenTag(), $token));
            $this->setProperty($formName, $form);
        }

        return $this->getProperty($formName);
	}

    /**
     * 获取表单
     * @param $name
     * @return array|mixed
     */
	public function get($name = null)
    {
        return $this->getProperty($name);
    }

    /**
     * 获取提交的表单
     * @param $requestToken
     * @return mixed
     * @throws Exception
     */
	public function getSubmitForm($requestToken)
	{
        if (empty($requestToken)) {
            $this->error->show('failed_validate_token');
        }

		$tokens = $this->session->get($this->getTokenListTag());
		$formName = array_search($requestToken, $tokens);

		if ($formName === false || !$this->hasProperty($formName)) {
            $this->error->show('not_exists_form');
        }

		$this->_form = $this->getProperty($formName);
		return $this->_form;
	}

    /**
     * 验证表单
     * @param $data
     * @return bool
     * @throws Exception
     */
	public function validate($data)
	{
	    $requestToken = ocGet($this->getTokenTag(), $data, null);
        $postForm = $this->getSubmitForm($requestToken);

		if ($postForm->validateForm()) {
			if (!$postForm->validate($this->validator, $data)) {
				$this->_showValidateError(
					'failed_validate_form',
					array($this->validator->getError()),
					$this->validator->getErrorSource()
				);
			}
		}

		return true;
	}

    /**
     * 获取TOKEN参数名称
     * @return string
     * @throws Exception
     */
    public function getTokenTag()
    {
        return '_oc_' . ocConfig('FORM.token_tag', '_form_token_name');
    }

    /**
     * 获取TOKEN参数名称
     * @return string
     * @throws Exception
     */
    public function getTokenListTag()
    {
        return $this->getTokenTag() . '_list';
    }

    /**
     * 保存TOKEN
     * @param $formName
     * @param $token
     * @throws Exception
     */
    public function saveToken($formName, $token)
    {
        ocService()->session->set(array($this->getTokenListTag(), $formName), $token);
    }

	/**
	 * 清理Token
	 */
	public function clearToken()
	{
        ocService()->session->delete($this->getTokenListTag());
	}

	/**
	 * 显示表单检测错误
	 * @param string $errorType
	 * @param array $params
	 * @param array $data
	 */
	private function _showValidateError($errorType, $params = array(), $data = array())
	{
		$error['errorType'] = $errorType;
		$error['errorInfo'] = ocService()->lang->get($errorType, $params);
		$error['errorData'] = $data;

		if ($this->event(self::EVENT_CHECK_ERROR)->get()) {
			$this->fire(self::EVENT_CHECK_ERROR, array($error, $this->getRoute()));
		}

        ocService()->error->show($error['errorInfo']);
	}
}