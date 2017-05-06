<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   安全过滤类Filter
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

final class Filter extends Base
{
	/**
	 * 单例模式
	 */
	private static $_instance = null;

	public static $_jsEvents = array();
	
	private function __clone(){}
	private function __construct(){}

	public static function getInstance()
	{
		if (self::$_instance === null) {
			self::$_instance = new self();
			self::_getEvents();
		}
		return self::$_instance;
	}

	/**
	 * 过滤SQL语句
	 * @param string|array $content
	 * @param bool $addSlashes
	 * @param array $keywords
	 * @param bool $equal
	 */
	public static function sql($content, $addSlashes = true, array $keywords = array(), $equal = false)
	{
		if (is_array($content)) {
			return array_map(__METHOD__, $content);
		} else {
			if ($keywords && ocConfig('DATABASE_FILTER_SQL_KEYWORDS', true)) {
				if ($equal) {
					if (in_array(strtolower($content), $keywords)) return false;
				} else {
					$content = str_ireplace($keywords, OC_EMPTY, (string)$content);
				}
			}
			return $addSlashes ? self::addSlashes($content) : $content;
		}
	}

	/**
	 * 过滤内容
	 * @param string|array $content
	 */
	public static function content($content)
	{
		return self::html(self::script($content));
	}
	
	/**
	 * 过滤HTML
	 * @param string|array $content
	 */
	public static function html($content)
	{
		if (is_array($content)) {
			return array_map(__METHOD__, $content);
		} else {
			if (function_exists('htmlspecialchars')) {
				return htmlspecialchars($content);
			}
			
			$search  = array('&', '"', "'", '<', '>');
			$replace = array('&amp;', '&quot;', '&#039;', '&lt;', '&gt;');
			return str_replace($search, $replace, $content);
		}
	}

	/**
	 * 过滤PHP标签
	 * @param string|array $content
	 */
	public static function php($content)
	{
		if (is_array($content)) {
			return array_map(__METHOD__, $content);
		} else {
			return str_replace(
				array('<?', '?>'),
				array('&lt;?', '?&gt;'),
				$content
			);
		}
	}

	/**
	 * 过滤脚本
	 * @param string|array $content
	 */
	public static function script($content)
	{
		if (is_array($content)) {
			return array_map(__METHOD__, $content);
		} else {
			$content = preg_replace('/<script[^>]*>.*<\/script>/i', OC_EMPTY, $content);
			$content = preg_replace('/<iframe[^>]*>.*<\/iframe>/i', OC_EMPTY, $content);
			$content = preg_replace('/<noframes[^>]*>.*<\/norame>/i', OC_EMPTY, $content);
			$content = preg_replace('/<object[^>]*>.*<\/object>/i', OC_EMPTY, $content);
			$content = preg_replace('/javascript:/i', OC_EMPTY, $content);
			
			$expression = '/(on('.self::$_jsEvents.'))|(('.self::$_jsEvents.')\((\s*function\()?)/i';
			$content = preg_replace($expression, OC_EMPTY, $content);
			
			return $content;
		}
	}

	/**
	 * 过滤路径
	 * @param string|array $path
	 */
	public static function path($path)
	{
		if (is_array($path)) {
			return array_map(__METHOD__, $path);
		} else {
			return preg_replace(
				'/\/{2,}|\\{1,}/',
				OC_DIR_SEP, preg_replace('/[^\w\/\]/',
				OC_EMPTY, $path)
			);
		}
	}

	/**
	 * 过滤Request来的数据
	 * @param string|array $content
	 */
	public static function request($content)
	{
		if (is_array($content)) {
			return array_map(__METHOD__, $content);
		} else {
			return addslashes(self::content($content));
		}
	}

	/**
	 * 过滤掉空白字符
	 * @param string|array $content
	 */
	public static function space($content)
	{
		if (is_array($content)) {
			return array_map(__METHOD__, $content);
		} else {
			return preg_replace('/\s+/', OC_EMPTY, $content);
		}
	}

	/**
	 * 将空白字符全替换掉
	 * @param string $str
	 * @param bool $replace
	 */
	public static function replaceSpace($str, $replace = OC_SPACE)
	{
		return preg_replace('/\s+/', $replace, $str);
	}

	/**
	 * 清除UTF-8下字符串的BOM字符
	 * @param string $content
	 */
	public static function bom($content)
	{
		if (is_array($content)) {
			return array_map(__METHOD__, $content);
		} else {
			if (substr($content, 0, 3) == chr(239) . chr(187) . chr(191)) {
				return ltrim($content, chr(239) . chr(187) . chr(191));
			}
			return $content;
		}
	}
	
	/**
	 * 转义
	 * @param string|array $content
	 */
	public static function addSlashes($content)
	{
		if (is_array($content)) {
			return array_map(__METHOD__, $content);
		} else {
			return addslashes($content);
		}
	}

	/**
	 * 去除转义
	 * @param string|array $content
	 */
	public static function stripSlashes($content)
	{
		if (is_array($content)) {
			return array_map(__METHOD__, $content);
		} else {
			return stripslashes($content);
		}
	}
	
	/**
	 * 去除换行符
	 * @param string|array $content
	 */
	public static function rn($content) 
	{
		if (is_array($content)) {
			return array_map(__METHOD__, $content);
		} else {
			return str_replace(array("\r\n", "\r", "\n"), OC_EMPTY, $content);
		}
	}

	/**
	 * 获取JS事件关键字
	 */
	private static function _getEvents()
	{
		$events = array(
			'click', 	'dbclick',    'change', 	'load', 	 'focus', 	 
			'mouseout', 'mouseover',  'mousedown', 	'mousemove', 'mouseup', 
			'submit',	'keyup', 	  'keypress',   'keydown',  'error',
			'abort', 	'resize', 	  'reset', 	  	'select', 	 'unload'
		);
		
		self::$_jsEvents = implode('|', $events);
	}
}