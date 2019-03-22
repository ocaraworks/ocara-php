<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架    基类Base
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

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
		return "\\" . get_called_class();
	}

    /**
     * 是否存在属性
     * @param string $name
     * @return bool
     */
    public function existsProperty($name)
    {
        return property_exists($this, $name) ? : $this->hasPlusProperty($name);
    }

    /**
     * 是否存在公有属性
     * @param string $name
     * @return bool
     */
    public function hasProperty($name)
    {
        return array_key_exists($name, (array)$this) || array_key_exists($name, $this->_properties);
    }

    /**
     * 获取属性
     * @param $name
     * @param null $default
     * @return bool
     */
    public function getProperty($name, $default = null)
    {
        return isset($this->$name) ? $this->$name : $default;
    }

    /**
     * 是否存在自定义属性
     * @param string $name
     * @return bool
     */
    public function hasPlusProperty($name)
    {
        return array_key_exists($name, $this->_properties);
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
     * 设置自定义的属性
     * @param $property
     * @param null $value
     */
    public function setPlusProperty($property, $value = null)
    {
        if (is_array($property)) {
            foreach ($property as $name => $value) {
                if (!property_exists($this, $property)) {
                    $this->_properties[$property] = $value;
                }
            }
        } else {
            if (!property_exists($this, $property)) {
                $this->_properties[$property] = $value;
            }
        }
    }

    /**
     * 获取自定义的属性数组
     * @param mixed $name
     * @return 自定义属性|null
     */
    public function getPlusProperty($name = null)
    {
        if (func_get_args()) {
            if (array_key_exists($name, $this->_properties)) {
                return $this->_properties[$name];
            }
            return null;
        }
        return $this->_properties;
    }

    /**
     * 删除自定义的属性
     * @param mixed $name
     */
    public function delPlusProperty($name)
    {
        $names = is_array($name) ? $name : func_get_args();

        foreach ($names as $name => $value) {
            if (array_key_exists($name, $this->_properties)) {
                $this->_properties[$name] = null;
                unset($this->_properties[$name]);
            }
        }
    }

    /**
     * 清理自定义属性
     */
    public function clearPlusProperty()
    {
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
        //When it is not private, protected. So it is not exists.
	    if (!property_exists($this, $property)) {
	        return isset($this->_properties[$property]);
        }
	}

	/**
	 * 魔术方法-读取不可访问属性的值时
	 * @param string $property
	 * @return mixed
	 * @throws Exception
	 */
	public function __get($property)
	{
        //get the property which is in the $_properties.
	    if (!property_exists($this, $property)) {
            if (array_key_exists($property, $this->_properties)) {
                $value = $this->_properties[$property];
                return $value;
            }
        }
	}

	/**
	 * 魔术方法-在给不可访问属性赋值时
	 * @param string $property
	 * @param mxied $value
	 * @return mixed
	 */
	public function __set($property, $value)
	{
        /*
         * append the property which is not exists.
         * When it is not private, protected. So it is not exists.
         */
	    if (!property_exists($this, $property)) {
            $this->_properties[$property] = $value;
        }
	}

	/**
	 * 魔术方法-当对不可访问属性调用 unset() 时
	 * @param string $property
	 */
	public function __unset($property)
	{
        //unset the property which is in the $_properties.
        if (!property_exists($this, $property)) {
            if (array_key_exists($property, $this->_properties)) {
                $this->_properties[$property] = null;
                unset($this->_properties[$property]);
            }
        }
	}
}