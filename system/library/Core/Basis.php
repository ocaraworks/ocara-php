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
	 * @var $_properties 自定义属性
	 */
	private $_properties = array();

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
     * 设置自定义的属性
     * @param $property
     * @param null $value
     */
    protected function _setProperty($property, $value = null)
    {
        if (is_array($property)) {
            foreach ($property as $name => $value) {
                $this->$name = $value;
            }
        } else {
            $this->$property = $value;
        }
    }

    /**
     * 清理属性
     * @param array $fields
     */
    protected function _clearProperties(array $fields = array())
    {
        $fields = $fields ? : array_keys($this->toArray());

        foreach ($fields as $field) {
            if (isset($this->$field)) {
                $this->$field = null;
            }
        }

        $this->_properties = array();
    }

    /**
     * 转换成公有属性数组
     * @return array
     */
    public function toArray()
    {
        $properties = json_decode(json_encode($this), true);
        return array_merge($properties, $this->_properties);
    }

    /**
     * 转换成公有属性对象
     */
    public function toObject()
    {
        return (object)$this->toArray();
    }

	/**
	 * 魔术方法-当对不可访问属性调用 isset() 或 empty()
	 * @param string $property
	 * @return bool
	 */
	public function __isset($property)
	{
        if (!property_exists($this, $property)) {
            return isset($this->_properties[$property]);
        }

        return false;
	}

	/**
	 * 魔术方法-读取不可访问属性的值时
	 * @param string $property
	 * @return mixed
	 * @throws Exception
	 */
	public function __get($property)
	{
	    if (!property_exists($this, $property)) {
            if (array_key_exists($property, $this->_properties)) {
                $value = $this->_properties[$property];
                return $value;
            }
        }

        $this->_throwAccessPropertyError($property);
	}

	/**
	 * 魔术方法-在给不可访问属性赋值时
	 * @param string $property
	 * @param mxied $value
	 * @return mixed
	 */
	public function __set($property, $value)
	{
	    if (!property_exists($this, $property)) {
            $this->_properties[$property] = $value;
            return true;
        }

        $this->_throwAccessPropertyError($property);
	}

	/**
	 * 魔术方法-当对不可访问属性调用 unset() 时
	 * @param string $property
	 */
	public function __unset($property)
	{
        if (!property_exists($this, $property)) {
            if (array_key_exists($property, $this->_properties)) {
                $this->_properties[$property] = null;
                unset($this->_properties[$property]);
                return true;
            }
        }

        $this->_throwAccessPropertyError($property);
	}

    /**
     * 找不到属性
     * @param $property
     * @throws Exception
     */
	protected function _throwAccessPropertyError($property)
    {
        $message = sprintf('Cannot access private or property %s::$%s', self::getClass(), $property);
        throw new Exception($message);
    }
}