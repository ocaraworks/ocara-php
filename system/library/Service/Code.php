<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   字符码生成插件Code
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Service;

use Ocara\Core\ServiceBase;

class Code extends ServiceBase
{
    /**
     * 生成随机友好字符（Ascii码值从 33到126）
     * @param $length
     * @return string|null
     */
	public static function getRand($length)
	{
		$rand = null;
		
		for ($i = 0;$i < $length;$i++) {
			$rand = $rand . chr(mt_rand(33, 126));
		}
		
		return $rand;
	}

    /**
     * 生成随机字符码
     * @param string $type
     * @param int $length
     * @param array $filter
     * @return mixed|string
     */
	public static function getCaptcha($type, $length, $filter = array())
	{
		$type = strtolower($type);
		$characters = array();
		
		if ($filter) {
			if (is_string($filter)) {
				$filter = array_chunk($filter, 1);
			} elseif (!is_array($filter)) {
				$filter = array();
			}
		} else {
			$filter = array();
		}
	
		if (strstr($type, 'letter') || $type == 'both') {
			for ($i = 65;$i <= 90;$i++) {
				$characters[] = chr($i);
			}
			for ($i = 97;$i <= 122;$i++) {
				$characters[] = chr($i);
			}
		}
	
		if (strstr($type, 'number') || $type == 'both') {
			if ($characters) {
				$characters = array_rand(array_flip($characters), 10);
			}
			for ($i = 0;$i <= 9;$i++) {
				$characters[] = (string)$i;
			}
		}
		
		if (!$characters) {
			return self::getCaptcha('both', $length, $filter);
		}
		
		$characters = array_diff($characters, $filter);
		shuffle($characters);
	
		if ($length > 10) {
			$count = intval($length / 10);
			$remainder = $length % 10;
			$result = null;
			for ($i = 1; $i <= $count; $i++) {
				$result = $result . self::getCaptcha($type, 10);
			}
			return $result . self::getCaptcha($type, $remainder);
		} else {
			$code = array_rand(array_flip($characters), $length);
			return is_array($code) ? implode(OC_EMPTY, $code) : $code;
		}
	}
}