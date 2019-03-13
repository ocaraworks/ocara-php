<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   表单令牌处理类FormToken
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Base;
use Ocara\Service\Code;

defined('OC_PATH') or exit('Forbidden!');

class FormToken extends Base
{
	/**
	 * @var $_tokenName 表单令牌键名名称
	 * @var $_tokenKey 表单令牌保存键名
	 */
	private $_formName;
	private $_tokenName;
	private $_tokenKey = array();

    /**
     * 设置当前表单令牌名称
     * @param $formName
     * @param $route
     */
	public function setCurrentForm($formName, $route)
	{
		$this->_formName  = $formName;
		$this->_tokenName = $this->genName($formName);
		$this->_tokenKey  = array(
			$this->_tokenName, implode('_', $route)
		);
	}

    /**
     * 设置当前表单令牌
     * @param $formName
     * @param $route
     * @return mixed|string
     * @throws \Ocara\Exceptions\Exception
     */
	public function setToken($formName, $route)
	{
		$this->setCurrentForm($formName);
		$token = $this->genToken($formName);

		list($tokenName, $tokenRoute) = $this->_tokenKey;
		$sessData = ocService()->session->get($tokenName);

		if (is_array($sessData)) {
			ocService()->session->set($this->_tokenKey, $token);
		} else {
			ocService()->session->set($tokenName, array($tokenRoute => $token));
		}

		return $token;
	}

    /**
     * 清除TOKEN
     * @throws \Ocara\Exceptions\Exception
     */
	public function clearToken()
	{
		$checkRepeatSubmit = ocConfig('FORM.check_repeat_submit', true);

		if ($checkRepeatSubmit && $this->has()) {
			ocService()->session->delete($this->_tokenKey);
			$this->_formName = null;
			$this->_tokenName = null;
			$this->_tokenKey = array();
		}
	}

	/**
	 * 是否存在表单
	 * @param string $formName
	 * @param string $token
	 * @return bool
	 */
	public function has($formName = null, $token = null)
	{
		if ($formName === null) {
			$tokenName = $this->_tokenName;
		} else {
			$tokenName = $this->genName($formName);
		}

		if (empty($tokenName)) {
			return false;
		}

		$exists = isset($_SESSION[$tokenName]) && $_SESSION[$tokenName] && is_array($_SESSION[$tokenName]);
		if ($token === null) {
			return $exists;
		}

		$exists = $exists && array_search($token, $_SESSION[$tokenName]);
		return $exists;
	}

	/**
	 * 获取表单令牌隐藏域名称
	 */
	public static function getTokenTag()
	{
		return '_oc_' . ocConfig('FORM.token_tag', 'temp_ocform_token');
	}

	/**
	 * 新建表单令牌名称
	 * @param string $formName
	 * @return string
	 */
	public function genName($formName)
	{
		$data = array($this->getTokenTag(), $formName);
		$tokenName = strtoupper(implode('_', $data));

		return $tokenName;
	}

    /**
     * 生成表单令牌
     * @param $formName
     * @return mixed|string
     * @throws \Ocara\Exceptions\Exception
     */
	public function genToken($formName)
	{
		$tag = self::getTokenTag();
		$route = ocService()->app->getRoute();
		$routeStr = implode(OC_EMPTY, $route);

		if ($config = ocConfig('SOURCE.form.generate_token', null)) {
			$token = call_user_func_array($config, array($tag, $formName, $route));
		} else {
			$token = md5($routeStr . $formName . md5(Code::getRand(5)) . uniqid(mt_rand()));
		}

		return $token;
	}
}