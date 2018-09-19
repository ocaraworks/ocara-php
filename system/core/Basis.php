<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架    基类Base
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

abstract class Basis
{
	/**
	 * @var $_properties 自定义属性
	 */
	protected $_properties = array();

    /**
     * 获取自定义属性
     * @param string $name
     * @param mixed $args
     * @return array|mixed
     */
	public function &getProperty($name = null)
	{
		if (isset($name)) {
			if (array_key_exists($name, $this->_properties)) {
				return $this->_properties[$name];
			}
		} else {
            return $this->_properties;
        }
	}

	/**
	 * 设置自定义属性
	 * @param mixed $name
	 * @param mixed $value
	 */
	public function setProperty($name, $value = null)
	{
		if (is_array($name)) {
			$this->_properties = array_merge($this->_properties, $name);
		} else {
			$this->_properties[$name] = $value;
		}
	}

	/**
	 * 设置自定义属性
	 * @param string $name
	 * @return bool
	 */
	public function hasProperty($name)
	{
		return array_key_exists($name, $this->_properties);
	}

    /**
     * 删除自定义属性
     * @param mixed $name
     */
    public function delProperty($name)
    {
        if (is_array($name)) {
            foreach ($name as $key){
                $this->__unset($key);
            }
        } else {
            $this->__unset($key);
        }
    }

    /**
     * 清理自定义属性
     */
	public function clearProperties()
	{
		$this->_properties = array();
	}

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
	 * 魔术方法-检测属性是否存在
	 * @param string $property
	 * @return bool
	 */
	public function __isset($property)
	{
		return $this->hasProperty($property);
	}

	/**
	 * 魔术方法-获取自定义属性
	 * @param string $property
	 * @return mixed
	 * @throws Exception
	 */
	public function __get($property)
	{
		if ($this->hasProperty($property)) {
			$value = $this->getProperty($property);
			return $value;
		}
	}

	/**
	 * 魔术方法-设置自定义属性
	 * @param string $property
	 * @param mxied $value
	 * @return mixed
	 */
	public function __set($property, $value)
	{
		return $this->_properties[$property] = $value;
	}

	/**
	 * 魔术方法-删除属性
	 * @param string $property
	 */
	public function __unset($property)
	{
        $this->_properties[$property] = null;
        unset($this->_properties[$property]);
	}
}