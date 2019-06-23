<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架    基类Base
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

abstract class Basis
{
	/**
	 * @var $properties 自定义属性
	 */
	private $properties = array();

	/**
	 * 返回当前类名（去除命名空间）
	 * @return string
	 */
	public static function getClassName()
	{
		$class = get_called_class();
		return substr($class, strrpos($class, "\\") + 1);
	}

	/**
	 * 返回当前类名（含命名空间）
	 * @return string
	 */
	public static function getClass()
	{
		return OC_NS_SEP . get_called_class();
	}

    /**
     * 转换成公有属性数组
     * @return array
     */
    public function toArray()
    {
        $properties = json_decode(json_encode($this), true);
        return array_merge($properties, $this->properties);
    }

    /**
     * 转换成公有属性对象
     */
    public function toObject()
    {
        return (object)$this->toArray();
    }
}