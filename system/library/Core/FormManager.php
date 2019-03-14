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

    /**
     * 注册服务
     * @throws Exception
     */
	public function register()
	{
		$validator = ocConfig('SERVICE.validator', '\Ocara\Core\Validator');
		$validate = ocConfig('SERVICE.validate', '\Ocara\Service\Validate');

		$this->_container
			 ->bindSingleton('validator', $validator, array($validate));
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
     * @param $name
     * @return mixed
     * @throws Exception
     */
	public function create($name)
	{
	    if (!$this->hasProperty($name)) {
            $form = $this->createService('form', array($name));
            $this->setProperty($name, $form);
        }
        return $this->getProperty($name);
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
     * @param $requests
     * @param $route
     * @return null
     * @throws Exception
     */
	public function getSubmitForm($requests, $route)
	{
        $tokenTag = $this->formToken->getTokenTag();
        $postToken = ocGet($tokenTag, $requests, null);
		$postForm  = null;

		if (empty($postToken)) {
			$this->_showCheckFormError('failed_validate_token');
		}

		$forms = $this->getProperty();
		foreach ($forms as $formName => $form) {
			if ($this->formToken->has($formName, $postToken)) {
				$postForm = $form;
				$this->formToken->setCurrentForm($formName, $route);
				break;
			}
		}

		if ($postForm === null) {
			$this->_showCheckFormError('not_exists_form');
		}

		return $postForm;
	}

    /**
     * 验证表单
     * @param $data
     * @return bool
     */
	public function validate($data)
	{
        $postForm = $this->formManager->getSubmitForm($data);

		if ($postForm->validateForm()) {
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
	 * 设置Token
	 */
	public function setToken($route)
	{
        $forms = $this->getProperty();

        if ($forms) {
            $tokenTag = $this->formToken->getTokenTag();
            foreach ($forms as $formName => $form) {
                if (is_object($form) && $form instanceof Form) {
                    $token = $this->formToken->setToken($formName, $route);
                    $form->setToken($tokenTag, $token);
                }
            }
        }
	}

	/**
	 * 清理Token
	 */
	public function clearToken()
	{
		$this->formToken->clearToken();
	}

    /**
     * 获取
     * @return mixed
     */
	public function getTokenTag()
    {
        return $this->foken->getTokenTag();
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
		$error['errorInfo'] = ocService()->lang->get($errorType, $params);;
		$error['errorData'] = $data;

		if ($this->event(self::EVENT_CHECK_ERROR)->get()) {
			$this->fire(self::EVENT_CHECK_ERROR, array($error, $this->getRoute()));
		}

        ocService()->error->show($error['errorInfo']);
	}
}