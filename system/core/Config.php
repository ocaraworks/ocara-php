<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 配置控制类Config
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

use Ocara\Exception\Exception;

defined('OC_PATH') or exit('Forbidden!');

final class Config extends Base
{
	/**
	 * 开关配置
	 */
	const YES = 1;
	const NO = 0;

	/**
	 * 数据变量
	 */
	protected $_ocData = array();

	/**
	 * 初始化
	 */
	public function __construct()
	{
		if (!file_exists($path = OC_SYS . 'data/default.php')) {
			throw new Exception('Lost ocara config file: default.php.');
		}
		
		include ($path);

		if (!(isset($OC_CONF) && $this->_ocData = $OC_CONF)) {
            throw new Exception('Lost config : $OC_CONF.');
		}

		if (is_dir($path = OC_ROOT . 'resource/conf')) {
			$this->load($path);
		}

		if (!$this->_properties) {
            throw new Exception('Lost config : $CONF.');
        }
	}

	/**
	 * 加载控制层配置
	 * @param string $dir
	 */
	public function loadControlConfig($route = array())
	{
        $path = OC_ROOT . 'resource/conf/control';
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
	 * 加载配置
	 * @param string|array $paths
	 */
	public function load($paths)
	{
		$CONF = &$this->_properties;
        $paths = ocForceArray($paths);

		foreach ($paths as $path) {
			if ($files = scandir($path)) {
				$config = $CONF;
				foreach ($files as $file) {
					if ($file == '.' || $file == '..') continue;
					$fileType = pathinfo($file, PATHINFO_EXTENSION);
					$file = $path . OC_DIR_SEP . $file;
					if (is_file($file) && $fileType == 'php') {
						include ($file);
					}
				}
				empty($CONF) && $CONF = $config;
			}
		}
	}
	
	/**
	 * 设置配置
	 * @param string|array $key
	 * @param mixed $value
	 */
	public function set($key, $value)
	{
		ocSet($this->_properties, $key, $value);
	}

    /**
     * 获取配置
     * @param string|array $key
     * @return array|bool|mixed|null
     */
    public function get($key = null)
    {
        if (isset($key)) {
            if (ocKeyExists($key, $this->_properties)) {
                return ocGet($key, $this->_properties);
            }
            return $this->getDefault($key);
        }

        return $this->_properties;
    }

    /**
     * 删除配置
     * @param string|array $key
     * @param mixed $value
     */
    public function delete($key)
    {
        ocDel($this->_properties, $key);
    }

	/**
	 * 获取默认配置
	 * @param string|array $key
	 * @return array|bool|mixed|null
	 */
	public function getDefault($key = null)
	{
		if (isset($key)) {
			return ocGet($key, $this->_ocData);
		}

		return $this->_ocData;
	}

	/**
	 * 检查配置键名是否存在
	 * @param string|array $key
	 * @return array|bool|mixed|null
	 */
	public function has($key = null)
	{
		return ocKeyExists($key, $this->_properties);
	}

	/**
	 * 获取配置
	 * @param string $key
	 * @return array|bool|null
	 */
	public function getConfig($key)
	{
		if (($result = ocCheckKey(false, $key, $this->_properties, true))
			|| ($result = ocCheckKey(false, $key, $this->_ocData, true))
		) {
			return $result;
		}
		
		return array();
	}
}