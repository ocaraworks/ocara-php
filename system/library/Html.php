<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   表单生成基类Html
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

class Html extends Base
{
	/**
	 * input
	 * @param string $type
	 * @param string $name
	 * @param array|string $attributes
	 * @param bool $checked
	 */
	public static function input($type, $name, $attributes = false, $checked = false)
	{
		$attributes = self::parseAttributes($attributes);
		$attributes['type'] = $type;
		
		if (($type == 'radio' || $type == 'checkbox') && $checked) {
			$attributes['checked'] = 'checked';
		} 
		
		return self::_createHtmlTag('input', $name, $attributes, false);
	}

	/**
	 * 解析属性数组
	 * @param array $attributes
	 */
	protected static function parseAttributes($attributes)
	{
		if (!is_array($attributes)) {
			$attributes = array(
				'value' => $attributes
			);
		}
		return $attributes;
	}

	/**
	 * 文本框
	 * @param string $name
	 * @param array|string $attributes
	 */
	public static function text($name, $attributes = false)
	{
		return self::input('text', $name, $attributes);
	}

	/**
	 * 密码框
	 * @param string $name
	 * @param array|string $attributes
	 */
	public static function password($name, $attributes = false)
	{
		return self::input('password', $name, $attributes);
	}
	
	/**
	 * 文本域
	 * @param string $name
	 * @param array|string $attributes
	 */
	public static function file($name, $attributes = false)
	{
		return self::input('file', $name, $attributes);
	}
	
	/**
	 * 隐藏域
	 * @param string $name
	 * @param array|string $attributes
	 */
	public static function hidden($name, $attributes = false)
	{
		return self::input('hidden', $name, $attributes);
	}
	
	/**
	 * 按钮
	 * @param string $name
	 * @param array|string $attributes
	 */
	public static function button($name, $attributes = false)
	{
		return self::_createHtmlTag('button', $name, $attributes, false);
	}

	/**
	 * 单选框
	 * @param string $name
	 * @param string $desc
	 * @param array|string $attributes
	 * @param bool $checked
	 */
	public static function radio($name, $desc, $attributes = false, $checked = false)
	{
		return self::input('radio', $name, $attributes, $checked) . $desc;
	}

	/**
	 * 批量单选框
	 * @param string $name
	 * @param array $radios
	 * @param array $checked
	 * @param array|string $attributes
	 */
	public static function radioMulti($name, $radios, $checked = array(), $attributes = array())
	{
		return self::getRadios($name, $radios, $checked, $attributes, 'radio');
	}

	/**
	 * 复选框
	 * @param string $name
	 * @param string $desc
	 * @param string|numric|array $attributes
	 * @param bool $checked
	 */
	public static function checkbox($name, $desc, $attributes = false, $checked = false)
	{
		return self::input('checkbox', $name, $attributes, $checked) . $desc;
	}

	/**
	 * 批量复选选框
	 * @param string $name
	 * @param array $checkboxes
	 * @param array $checked
	 * @param string|numric|array $attributes
	 */
	public static function checkboxMulti($name, array $checkboxes, $checked = array(), $attributes = array())
	{
		return self::getRadios($name, $checkboxes, $checked, $attributes, 'checkbox');
	}
	
	/**
	 * 批量生成单选或复选框
	 * @param string $name
	 * @param array $data
	 * @param array $checked
	 * @param string|numric|array $attributes
	 * @param string $method
	 */
	protected static function getRadios($name, $data, $checked, $attributes, $method)
	{
		$checked = (array)$checked;
		$boxes   = array();
		$index   = 1;
		$tagName = $method == 'checkbox' ? rtrim($name, '[]') . '[]' : $name;

		foreach ($data as $value => $row) {
			$attrs = $attributes;
			if (is_string($row)) {
				$desc = $row;
			} else {
				$desc = $row[0];
				if (is_array($row[1])) {
					$attrs = array_merge($attrs, $row[1]);
				}
			}

			$attrs['id']    = $name . '_' . $index++;
			$attrs['value'] = $value;

			$params = array(
				$tagName, $desc, $attrs, in_array($value, $checked)
			);
			$boxes[] = call_user_func_array(array(__CLASS__, $method), $params);
		}

		return $boxes;
	}
	
	/**
	 * 下拉框
	 * @param string $name
	 * @param array $options
	 * @param string|numric|array $attributes
	 * @param string $nullText
	 * @param bool $optgroup
	 */
	public static function select($name, $options = array(), $attributes = false, $nullText = false, $optgroup = false)
	{
		$value = false;
		
		if (is_numeric($attributes) || is_string($attributes)) {
			$value = $attributes;
		} elseif (is_array($attributes)) {
			$value = ocDel($attributes, 'value');
			if (array_key_exists('(value)', $attributes)) {
				$attributes['value'] = ocDel($attributes, '(value)');
			}
		} 
		
		$option     = self::options($options, $value, $nullText, $optgroup);
		$content    = $option ? $option : true;
		$attributes = $attributes && is_array($attributes) ? $attributes : array();
		$attributes = self::parseAttributes($attributes);
		$result     = self::_createHtmlTag('select', $name, $attributes, $content);

		return $result;
	}

	/**
	 * 分组下拉框
	 * @param string $name
	 * @param array $options
	 * @param string|numric|array $attributes
	 * @param string $nullText
	 */
	public static function selectGroup($name, $options, $attributes = false, $nullText = false) 
	{
		return self::select($name, $options, $attributes, $nullText, true);
	}
	
	/**
	 * 下拉选项
	 * @param array $options
	 * @param string $value
	 * @param string $nullText
	 * @param bool $optgroup
	 */
	public static function options($options, $value = null, $nullText = null, $optgroup = false)
	{
		if($nullText) {
			$attrs = array('value'=> false);
			$nullText = self::_createHtmlTag('option', false, $attrs, $nullText ? $nullText : true);
		}

		if (is_array($options)) {
			$str = false;
			if ($optgroup) {
				foreach ($options as $row) {
					if (isset($row['optgroup']) && $row['optgroup']) {
						$str = $str . "<optgroup label=\"{$row['optgroup']}\">";
						$str = $str . self::getOptions($row['options'], $value);
						$str = $str . "</optgroup>";
					}
				}
			} else {	
				$str = $str . self::getOptions($options, $value);
			}
			return $nullText . $str;
		}
		
		return $nullText;
	}
	
	/**
	 * 获取选项框
	 * @param array $options
	 * @param string $value
	 */
	protected static function getOptions($options, $value) 
	{
		$str = false;
		
		if (empty($options)) return false;
		
		foreach ($options as $key => $val) {
			$attrs = array('value'=> $key);
			if ((string)$value == (string)$key) {
				$attrs['selected'] = 'selected';
			}
			$str = $str .
				self::_createHtmlTag(
					'option', false, $attrs, $val ? $val : true
				);
		}

		return $str;
	}
	
	/**
	 * 文本域textarea
	 * @param string $name
	 * @param string|numric|array $attributes
	 */
	public static function textarea($name, $attributes = false)
	{
		if (is_array($attributes)) {
			if (array_key_exists('value', $attributes)) {
				$value = ocDel($attributes, 'value');
			} else {
				$value = false;
			}
			if (array_key_exists('(value)', $attributes)) {
				$attributes['value'] = ocDel($attributes, '(value)');
			}
		} else {
			$value = $attributes;
			$attributes = array();
		}

		$content = $value ? $value : true;
		$result  = self::_createHtmlTag('textarea', $name, $attributes, $content);

		return $result;
	}

	/**
	 * 新增HTML标签
	 * @param string $type
	 * @param string $name
	 * @param array $attributes
	 * @param string $content
	 */
	private static function _createHtmlTag($type, $name, array $attributes = array(), $content = true)
	{
		$type = strtolower($type);

		if($name) {
			$array 		= is_array($name) ? $name : array($name, $name);
			$name  		= reset($array);
			$id    		= isset($array[1]) ? $array[1] : null;
			$array 		= $id ? compact('name', 'id') : array('name' => $name);
			$attributes = array_merge($array, $attributes);
		} 
		
		$html = sprintf("<%s%s", $type, self::getAttr($attributes));

		if ($content) {
			$content = $content === true ? false : $content;
			$content = ">{$content}</{$type}>";
		} else {
			$content =  $content === false ? '/>' : '>';
		}
		
		return $html . $content;
	}

	/**
	 * 新增HTML元素
	 * @param string $type
	 * @param array $attributes
	 * @param string $content
	 */
	public static function createElement($type, array $attributes = array(), $content = true)
	{
		return self::_createHtmlTag($type, false, $attributes, $content);
	}

	/**
	 * 新增结束HTML标记
	 * @param $name
	 * @return string
	 */
	public static function createEndHtmlTag($name)
	{
		return '</' . $name . '>';
	}

	/**
	 * 属性连接串
	 * @param array $attributes
	 */
	public static function getAttr(array $attributes)
	{
		$str = false;
		foreach ($attributes as $key => $value) {
			$str = $str . OC_SPACE . "{$key}=\"$value\"";
		}
		return $str;
	}
}