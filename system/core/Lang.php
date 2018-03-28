<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 语言配置控制类Lang
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

use Ocara\Exception\Exception;

defined('OC_PATH') or exit('Forbidden!');

class Lang extends Base
{
	protected $_properties = null;
	protected $_ocData = null;

	/**
	 * 初始化
	 * @throws Exception
	 */
	public function __construct()
	{
		if  ($this->_ocData === null){
			$this->_ocData = array();
			$file = Ocara::language() . '.php';
			$path = OC_SYS . 'data/languages/' . $file;

			if (file_exists($path)) {
				$lang = include ($path);
				if ($lang) {
					$this->_ocData = ocForceArray($lang);
				}
			}
		}

		if ($this->_properties === null) {
			$this->_properties = array();
			$this->loadApplicationConfig('lang', Ocara::language(), 'control');
		}
	}

	/**
	 * 应用级配置
	 * @param string $dir
	 * @param string $type
	 * @param string $sub
	 */
	public function loadApplicationConfig($dir, $type, $sub = null)
	{
		$path  = OC_ROOT . 'resource/' . $dir;
		extract(Ocara::getRoute());
		$paths = array();

		if (is_dir($path)) {
			$paths[] = $path;
		}

		if (is_dir($path = $path . OC_DIR_SEP . $type)) {
			$paths[] = $path;
			if ($sub && is_dir($path = $path . OC_DIR_SEP . $sub)) {
				$paths[] = $path;
			}
			if (isset($module) && $module && is_dir($path = $path . OC_DIR_SEP . $module)) {
				$paths[] = $path;
				if ($controller && is_dir($path = $path . OC_DIR_SEP . $controller)) {
					$paths[] = $path;
					if ($action && is_dir($path = $path . OC_DIR_SEP . $action)) {
						$paths[] = $path;
					}
				}
			}
		}

		$lang = $this->loadControlConfig($paths);
		if ($lang) {
			array_unshift($lang, $this->_properties);
			$this->_properties = call_user_func_array('array_merge', $lang);
		}
	}

	/**
	 * 加载语言配置
	 * @param array $paths
	 * @return array
	 */
	public function loadControlConfig($paths)
	{
		$path = ocForceArray($paths);
		$data = array();

		foreach ($path as $value) {
			if ($files = scandir($value)) {
				foreach ($files as $file) {
					if ($file == '.' or $file == '..') continue;
					$fileType = pathinfo($file, PATHINFO_EXTENSION);
					if (is_file($file = $value . OC_DIR_SEP . $file) && $fileType == 'php') {
						$row = @include ($file);
						if ($row && is_array($row)) {
							$data[] = $row;
						}
					}
				}
			}
		}

		return $data;
	}

    /**
     * 获取语言配置（方法重写）
     * @param string $name
     * @param mixed $args
     * @return array|null
     */
    public function &get($name = null, $args = null)
    {
		if (func_num_args()) {
            $args = func_get_args();
            $params = array_key_exists(1, $args) ? (array)$args[1] : array();
			if (ocKeyExists($name, $this->_properties)) {
                $value =  ocGetLanguage($this->_properties, $name, $params);
			} else {
                $value = $this->getDefault($name, $params);
            }
			return $value;
		}

		return $this->_properties;
	}

	/**
	 * 获取默认语言
	 * @param string $key
	 * @param array $params
	 * @return array|null
	 * @throws Exception
	 */
	public function getDefault($key = null, array $params = array())
	{
		if (func_num_args()) {
			return ocGetLanguage($this->_ocData, $key, $params);
		}

		return $this->_ocData;
	}

	/**
	 * 设置语言
	 * @param string|array $name
	 * @param mixed $value
	 */
	public function set($name, $value = null)
	{
		ocSet($this->_properties, $name, $value);
	}

	/**
	 * 检查语言键名是否存在
	 * @param string|array $key
	 * @return array|bool|mixed|null
	 * @throws Exception
	 */
	public function exists($key = null)
	{
		return ocKeyExists($key, $this->_properties);
	}

	/**
	 * 删除语言配置
	 * @param string|array $key
	 * @return array|null
	 * @throws Exception
	 */
	public function delete($key)
	{
		return ocDel($this->_properties, $key);
	}
}