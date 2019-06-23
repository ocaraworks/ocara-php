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

abstract class BaseEntity
{
	/**
	 * @var $properties 自定义属性
	 */
	private $properties = array();

    /**
     * 设置属性
     * @param $property
     * @param null $value
     */
    protected function setProperty($property, $value = null)
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
    protected function clearProperties(array $fields = array())
    {
        $fields = $fields ? : array_keys($this->toArray());

        foreach ($fields as $field) {
            if (isset($this->$field)) {
                $this->$field = null;
            }
        }

        $this->properties = array();
    }

	/**
	 * 魔术方法-当对不可访问属性调用 isset() 或 empty()
	 * @param string $property
	 * @return bool
	 */
	public function __isset($property)
	{
        if ($property != 'properties' && !property_exists($this, $property)) {
            return isset($this->properties[$property]);
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
	    if ($property != 'properties' && !property_exists($this, $property)) {
            if (array_key_exists($property, $this->properties)) {
                $value = $this->properties[$property];
                return $value;
            } else {
                $message = sprintf('Not Found property %s::$%s', self::getClass(), $property);
                throw new Exception($message);
            }
        }

        $this->throwAccessPropertyError($property);
	}

    /**
     * 在给不可访问属性赋值时
     * @param $property
     * @param $value
     * @return bool
     * @throws Exception
     */
	public function __set($property, $value)
	{
	    if ($property != 'properties' && !property_exists($this, $property)) {
            $this->properties[$property] = $value;
            return true;
        }

        $this->throwAccessPropertyError($property);
	}

    /**
     * 当对不可访问属性调用 unset() 时
     * @param $property
     * @return bool
     * @throws Exception
     */
	public function __unset($property)
	{
        if ($property != 'properties' && !property_exists($this, $property)) {
            if (array_key_exists($property, $this->properties)) {
                $this->properties[$property] = null;
                unset($this->properties[$property]);
                return true;
            }
        }

        $this->throwAccessPropertyError($property);
	}

    /**
     * 找不到属性
     * @param $property
     * @throws Exception
     */
	protected function throwAccessPropertyError($property)
    {
        $message = sprintf('Cannot access private or property %s::$%s', self::getClass(), $property);
        throw new Exception($message);
    }
}