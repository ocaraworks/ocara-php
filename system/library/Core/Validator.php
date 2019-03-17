<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架     验证器类Validator
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Base;

defined('OC_PATH') or exit('Forbidden!');

class Validator extends Base
{
	private $_errorExists;
	private $_errorSource;
	private $_error;
	private $_errorLocation;
	private $_validate;

	protected $_rules = array();
	protected $_lang = array();

	/**
	 * @param object $validate
	 */
	public function __construct($validate)
	{
		if (!is_object($validate)) {
			$validate = new $validate();
		}
		$this->_validate = $validate;
	}

    /**
     * 表单验证
     * @param array $data
     * @return bool
     * @throws \Ocara\Exceptions\Exception
     */
	public function validate(array $data)
	{
		$result = true;
		$rules = $this->_rules;
		$lang = array_merge(ocService()->lang->get(), $this->_lang);

		if ($rules) foreach ($rules as $field => $rule) {
			if (empty($rule)) continue;
			if(is_string($rule)) $rule = array('common' => $rule);
			$value = ocGet($field, $data);
			$value = $value === null ? OC_EMPTY : $value;
			$value = (array)$value;

			if (isset($rule['common']) && $rule['common'] && is_string($rule['common'])) {
				$result = $this->common($field, $value, $rule['common']);
			} elseif (isset($rule['expression']) && $rule['expression'] && is_string($rule['expression'])) {
				$result = $this->expression($field, $value, $rule['expression']);
			} elseif (isset($rule['callback']) && $rule['callback'] && is_string($rule['callback'])) {
				$result = $this->callback($field, $value, $rule['callback']);
			}

			if (!$result) {
				$this->setError($lang);
				break;
			}
		}

		return $result;
	}

	/**
	 * 设置规则
	 * @param array $rules
	 * @return $this
	 */
	public function setRules(array $rules)
	{
		$this->_rules = $rules;
		return $this;
	}

	/**
	 * 设置语言文本
	 * @param array $lang
	 * @return $this
	 */
	public function setLang(array $lang)
	{
		$this->_lang = $lang;
		return $this;
	}

	/**
	 * 增加验证规则
	 * @param $field
	 * @param null $rule
	 * @return $this
	 */
	public function addRule($field, $rule = null)
	{
		$this->_rules[$field] = $rule;
		return $this;
	}

	/**
	 * 增加语言文本
	 * @param $key
	 * @param null $value
	 * @return $this
	 */
	public function addLang($key, $value = null)
	{
		$this->_lang[$key] = $value;
		return $this;
	}

	/**
	 * 普通验证
	 * @param string $field
	 * @param string $value
	 * @param mixed $validates
	 * @return bool
	 */
	public function common($field, $value, $validates)
	{
		if (is_string($validates)) {
			$validates = $validates ? explode('|', trim($validates)) : array();
		}

		$validates = array_map('trim', $validates);
		$count     = count($value);

		foreach ($validates as $validate) {
			$params = array();
			if (preg_match('/^([a-zA-Z]+)\:(.+)?$/', $validate, $matches)) {
				$matches = array_map('trim', $matches);
				$method = $matches[1];
				if (isset($matches[2])) {
					$params = array_map('trim', explode(',', $matches[2]));
				}
			} else {
				$method = $validate;
			}

			for ($i = 0; $i < $count; $i++) {
				$val   = $value[$i];
				$args  = array_merge(array($val), $params);
				$error = call_user_func_array(array(&$this->_validate, $method), $args);
				if ($error) {
					$this->prepareError($error, $field, $val, $i, $params);
					return false;
				}
			}
		}
		
		return true;
	}

    /**
     * 正则表达式验证
     * @param $field
     * @param $value
     * @param $expression
     * @return bool
     * @throws \Ocara\Exceptions\Exception
     */
	public function expression($field, $value, $expression)
	{
		$count = count($value);
		
		for ($i = 0; $i < $count; $i++) {
			$val 		= $value[$i];
			$expression = (array)$expression;
			$rule 		= reset($expression);
			$newError 	= ocGet(1, $expression);

			$error = call_user_func_array(
				array(&$this->_validate, 'regExp'),
				array($val, $rule)
			);

			if ($error) {
				$error = $newError ? : $error;
				$this->prepareError($error, $field, $val, $i);
				return false;
			}
		}
		
		return true;
	}

	/**
	 * 回调函数验证
	 * @param string $field
	 * @param string $value
	 * @param string|array $callback
	 * @return bool
	 * @throws Exception\Exception
	 */
	public function callback($field, $value, $callback)
	{
		if(empty($callback)) {
			ocService()->error->show('fault_callback_validate');
		}

		$count = count($value);
		for ($i = 0; $i < $count; $i++) {
			$val   = $value[$i];
			$error = call_user_func_array($callback, array($field, $val, $i));
			if ($error) {
				$this->prepareError($error, $field, $val, $i);
				return false;
			}
		}
		
		return true;
	}

	/**
	 * 是否验证错误
	 */
	public function errorExists()
	{
		return $this->_errorExists;
	}

	/**
	 * 获取错误消息
	 */
	public function getError()
	{
		return $this->_error;
	}
	
	/**
	 * 获取错误字段
	 */
	public function getErrorSource()
	{
		return $this->_errorSource;
	}
	
	/**
	 * 设置验证错误
	 * @param string $errorData
	 * @param string $field
	 * @param string $value
	 * @param integer $index
	 * @param array $params
	 */
	public function prepareError($errorData, $field, $value, $index, $params = array())
	{
		list($error, $message) = $errorData;
		$params = array_values($params);
		$this->_errorLocation = array($error, $message, $field, $value, $index, $params);
	}

    /**
     * 绑定错误
     * @param array $lang
     */
	public function setError(array $lang)
	{
		list($error, $message, $field, $value, $index, $params) = $this->_errorLocation;
		$desc = ocGet($field, $lang, $field);

		if (is_array($error)) {
			$error = ocGet('message', $error);
		}

		if (isset($lang[$error])) {
			$message = $lang[$error];
		}

		$error = str_ireplace('{field}', $desc, $message);
		foreach ($params as $key => $value) {
			str_ireplace('{'.($key + 1).'}', $value, $message);
		}

		$this->_errorExists	= true;
		$this->_error = $error;
		$this->_errorSource = array($field, $value, $index);
	}
}
