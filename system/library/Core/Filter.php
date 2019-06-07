<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   安全过滤类Filter
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Base;
use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class Filter extends Base
{
	protected $jsEvents;

	/**
	 * 初始化
	 * Filter constructor.
	 */
	public function __construct()
	{
		$this->jsEvents = implode('|', ocConfig('JS_EVENTS', array()));
	}

    /**
     * 过滤SQL语句
     * @param string|array $content
     * @param bool $addSlashes
     * @param array $keywords
     * @param bool $equal
     * @return array|bool|mixed|string
     * @throws Exception
     */
	public function sql($content, $addSlashes = true, array $keywords = array(), $equal = false)
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
			return $addSlashes ? $this->addSlashes($content) : $content;
		}
	}

	/**
	 * 过滤内容
	 * @param string|array $content
	 * @return array|mixed|string
	 */
	public function content($content)
	{
		return $this->html($this->script($content));
	}

	/**
	 * 过滤HTML
	 * @param string|array $content
	 * @return array|mixed|string
	 */
	public function html($content)
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
	 * @return array|mixed
	 */
	public function php($content)
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
	 * @return array|mixed
	 */
	public function script($content)
	{
		if (is_array($content)) {
			return array_map(__METHOD__, $content);
		} else {
			$content = preg_replace('/<script[^>]*>.*<\/script>/i', OC_EMPTY, $content);
			$content = preg_replace('/<iframe[^>]*>.*<\/iframe>/i', OC_EMPTY, $content);
			$content = preg_replace('/<noframes[^>]*>.*<\/norame>/i', OC_EMPTY, $content);
			$content = preg_replace('/<object[^>]*>.*<\/object>/i', OC_EMPTY, $content);
			$content = preg_replace('/javascript:/i', OC_EMPTY, $content);

			$expression = '/(on('.$this->jsEvents.'))|(('.$this->jsEvents.')\((\s*function\()?)/i';
			$content = preg_replace($expression, OC_EMPTY, $content);
			
			return $content;
		}
	}

	/**
	 * 过滤路径
	 * @param string|array $path
	 * @return array|mixed
	 */
	public function path($path)
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
	 * @return array|string
	 */
	public function request($content)
	{
		if (is_array($content)) {
			return array_map(__METHOD__, $content);
		} else {
			return addslashes($this->content($content));
		}
	}

	/**
	 * 过滤掉空白字符
	 * @param string|array $content
	 * @return array|mixed
	 */
	public function space($content)
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
	 * @param string $replace
	 * @return mixed
	 */
	public function replaceSpace($str, $replace = OC_SPACE)
	{
		return preg_replace('/\s+/', $replace, $str);
	}

	/**
	 * 清除UTF-8下字符串的BOM字符
	 * @param string $content
	 * @return array|string
	 */
	public function bom($content)
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
	 * @return array|string
	 */
	public function addSlashes($content)
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
	 * @return array|string
	 */
	public function stripSlashes($content)
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
	 * @return array|mixed
	 */
	public function rn($content) 
	{
		if (is_array($content)) {
			return array_map(__METHOD__, $content);
		} else {
			return str_replace(array("\r\n", "\r", "\n"), OC_EMPTY, $content);
		}
	}
}