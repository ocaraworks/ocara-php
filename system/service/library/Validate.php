<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  表单验证插件Validate
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Service;
use Ocara\ServiceBase;
use Ocara\Error;

class Validate extends ServiceBase
{
	/**
	 * 不能为非0的空值
	 * @param string $value
	 */
	public static function unempty($value)
	{
		$result = !ocEmpty($value);
		$result = self::validate($result, 'unempty');
		return $result;
	}

	/**
	 * 标准命名方式
	 * @param string $value
	 */
	public static function standardName($value)
	{
		$result = preg_match('/^[a-zA-Z_]+[a-zA-Z0-9_]*$/', $value);
		$result = self::validate($result, 'is_not_standard_name');
		return $result;
	}

	/**
	 * 最长字符
	 * @param string $value
	 * @param integer $length
	 */
	public static function maxLength($value, $length = 0)
	{
		$num = func_num_args();
		self::_checkArgsNum(3, $num);

		$result = strlen($value) <= $length;
		$result = self::validate($result, 'over_max_string_length', array($length));
		return $result;
	}

	/**
	 * 最短字符
	 * @param string $value
	 * @param integer $length
	 */
	public static function minLength($value, $length = 0)
	{
		$num = func_num_args();
		self::_checkArgsNum(3, $num);

		$result = strlen($value) >= $length;
		$result = self::validate($result, 'less_than_min_string_length', array($length));
		return $result;
	}

	/**
	 * 字符字数
	 * @param string $value
	 * @param integer $min
	 * @param integer $max
	 */
	public static function betweenLength($value, $min = 0, $max = 1)
	{
		$num = func_num_args();
		self::_checkArgsNum(4, $num);

		$len    = strlen($value);
		$result = $len >= $min && $len <= $max;
		$result = self::validate($result, 'not_in_pointed_length',  array($min, $max));
		return $result;
	}
	
	/**
	 * email验证
	 * @param string $value
	 */
	public static function email($value)
	{
		$result = filter_var($value, FILTER_VALIDATE_EMAIL);
		$result = self::validate($result, 'unvalid_email');
		return $result;
	}

	/**
	 * IP验证
	 * @param string $value
	 */
	public static function ip($value)
	{
		$result = filter_var($value, FILTER_VALIDATE_IP);
		$result = self::validate($result, 'unvalid_ip');
		return $result;
	}

	/**
	 * URL验证
	 * @param string $value
	 */
	public static function url($value)
	{
		$result = filter_var($value, FILTER_VALIDATE_URL);
		$result = self::validate($result, 'unvalid_url');
		return $result;
	}

	/**
	 * 正则表达式验证
	 * @param string $value
	 * @param string $expression
	 */
	public static function regExp($value, $expression = '')
	{
		$num = func_num_args();
		self::_checkArgsNum(3, $num);

		$result = preg_match($expression, $value);
		$result = self::validate($result, 'unvalid_express_format', array($expression));
		return $result;
	}

	/**
	 * 身份证验证
	 * @param string $value
	 */
	public static function idCard($value)
	{
		$result = preg_match('/^\d{15}|\d{18}$/', $value);
		$result = self::validate($result, 'unvalid_id_cards');
		return $result;
	}

	/**
	 * 验证手机号码
	 * @param string $value
	 */
	public static function mobileTel($value)
	{
		$result = preg_match('/^[1]\d{10}$/', $value);
		$result = self::validate($result, 'unvalid_mobile');
		return $result;
	}

	/**
	 * 验证是否全部是中文
	 * @param string $value
	 */
	public static function chinese($value)
	{
		$result = preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $value);
		$result = self::validate($result, 'must_be_chinese_all');
		return $result;
	}

	/**
	 * 验证是否含有中文
	 * @param string $value
	 */
	public static function noneChinese($value)
	{
		$result = !preg_match('/[\x{4e00}-\x{9fa5}]+/u', $value);
		$result = self::validate($result, 'cannot_have_chinese');
		return $result;
	}

	/**
	 * 验证邮政编码
	 * @param string $value
	 */
	public static function postNum($value)
	{
		$result = !preg_match('/^[1-9]\d{5}(?!\d)$/', $value);
		$result = self::validate($result, 'unvalid_post_num');
		return $result;
	}

	/**
	 * 验证
	 * @param string $expression
	 * @param string $error
	 * @param array $params
	 */
	public static function validate($expression, $error, array $params = array())
	{
		if ($expression !== true) {
			return array($error, self::getMessage($error, $params));
		}
		return array();
	}

	/**
	 * @param integer $need
	 * @param integer $num
	 */
	protected static function _checkArgsNum($need, $num)
	{
		if ($need > $num) {
			Error::show('invalid_args_num');
		}
	}
}
