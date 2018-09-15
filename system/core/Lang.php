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
			$this->load(OC_ROOT . 'resource/lang/' . Ocara::language());
		}
	}

    /**
     * 加载控制层语言
     * @param string $path
     */
    public function loadControlLang($route)
    {
        $path = OC_ROOT . 'resource/lang/' . Ocara::language();
        $paths = array();
        extract($route);

        if (isset($module) && $module && is_dir($path . OC_DIR_SEP . $module)) {
            $path = $path . OC_DIR_SEP . $module;
            $paths[] = $path;
        }

        if ($controller && is_dir($path = $path . OC_DIR_SEP . $controller)) {
            $paths[] = $path;
            if ($action && is_dir($path = $path . OC_DIR_SEP . $action)) {
                $paths[] = $path;
            }
        }

        $this->load($paths);
    }

	/**
	 * 加载语言配置
	 * @param array $paths
	 * @return array
	 */
	public function load($paths)
	{
		$path = ocForceArray($paths);
		$data = array();

		foreach ($path as $path) {
			if ($files = scandir($path)) {
				foreach ($files as $file) {
					if ($file == '.' || $file == '..') continue;
					$fileType = pathinfo($file, PATHINFO_EXTENSION);
					if (is_file($file = $path . OC_DIR_SEP . $file) && $fileType == 'php') {
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
     * @param string $key
     * @param mixed $args
     * @return array|null
     */
    public function get($key = null, array $params = array())
    {
		if (isset($key)) {
			if (ocKeyExists($key, $this->_properties)) {
                $value =  ocGetLanguage($this->_properties, $key, $params);
			} else {
                $value = $this->getDefault($key, $params);
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
		if (isset($key)) {
			return ocGetLanguage($this->_ocData, $key, $params);
		}

		return $this->_ocData;
	}

	/**
	 * 设置语言
	 * @param string|array $key
	 * @param mixed $value
	 */
	public function set($key, $value = null)
	{
		ocSet($this->_properties, $key, $value);
	}

	/**
	 * 检查语言键名是否存在
	 * @param string|array $key
	 * @return array|bool|mixed|null
	 * @throws Exception
	 */
	public function has($key = null)
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