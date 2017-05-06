<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   表单令牌处理类FormToken
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;
use Ocara\Service\Code;

defined('OC_PATH') or exit('Forbidden!');

final class FormToken extends Base
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
	 * @param string $formName
	 */
	public function setCurrentForm($formName)
	{
		$this->_formName  = $formName;
		$this->_tokenName = $this->genName($formName);
		$this->_tokenKey  = array(
			$this->_tokenName, implode('_', $this->getRoute())
		);
	}

	/**
	 * 设置当前表单令牌
	 * @param string $formName
	 */
	public function setToken($formName)
	{
		$this->setCurrentForm($formName);
		$token = $this->genToken($formName);

		list($tokenName, $tokenRoute) = $this->_tokenKey;
		$sessData = Session::get($tokenName);

		if (is_array($sessData)) {
			Session::set($this->_tokenKey, $token);
		} else {
			Session::set($tokenName, array($tokenRoute => $token));
		}

		return $token;
	}

	/**
	 * 删除表单
	 */
	public function clear()
	{
		$checkRepeatSubmit = ocConfig('FORM.check_repeat_submit', true);

		if ($checkRepeatSubmit && $this->exists()) {
			Session::delete($this->_tokenKey);
			$this->_formName = null;
			$this->_tokenName = null;
			$this->_tokenKey = array();
		}
	}

	/**
	 * 是否存在表单
	 */
	public function exists($formName = null, $token = null)
	{
		if ($formName === null) {
			$tokenName = $this->_tokenName;
		} else {
			$tokenName = $this->genName($formName);
		}

		if (empty($tokenName)) {
			return false;
		}

		$exists = isset($_SESSION[$tokenName])
			&& $_SESSION[$tokenName]
			&& is_array($_SESSION[$tokenName]);

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
	 */
	public function genName($formName)
	{
		$data = array($this->getTokenTag(), $formName);
		$tokenName = strtoupper(implode('_', $data));

		return $tokenName;
	}

	/**
	 * 生成表单令牌
	 * @param string $formName
	 */
	public function genToken($formName)
	{
		$tag = self::getTokenTag();
		$route = $this->getRoute();
		$routeStr = implode(OC_EMPTY, $route);

		if ($config = ocConfig('CALLBACK.form.generate_token', null)) {
			$token = Call::run($config, array($tag, $formName, $route));
		} else {
			$token = md5($routeStr . $formName . md5(Code::getRand(5)) . uniqid(mt_rand()));
		}

		return $token;
	}
}