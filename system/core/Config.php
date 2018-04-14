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
			$this->loadControlConfig($path);
		}

		if (!$this->_properties) {
            throw new Exception('Lost config : $CONF.');
        }
	}

	/**
	 * 获取基本配置和模块配置
	 * @param string $dir
	 * @param string $type
	 * @param string $sub
	 * @param string $module
	 */
	public function loadModuleConfig($dir, $type, $sub, $module)
	{
		$path  = OC_ROOT . 'resource/' . $dir;
		$paths = array();
		if (is_dir($path)) {
			$paths[] = $path;
			if (is_dir($path = $path . OC_DIR_SEP . $type)) {
				$paths[] = $path;
				if ($sub && is_dir($path = $path . OC_DIR_SEP . $sub)) {
					$paths[] = $path;
				}
				if ($module && is_dir($path = $path . OC_DIR_SEP . $module)) {
					$paths[] = $path;
				}
			}
		}

		$this->loadControlConfig($paths);
	}

	/**
	 * 加载控制器动作的配置
	 * @param string $path
	 */
	public function loadActionConfig($path)
	{
		$path = OC_ROOT . 'resource/' . rtrim($path, OC_DIR_SEP);
		$paths = array();
		extract(Ocara::getRoute());

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

		$this->loadControlConfig($paths);
	}

	/**
	 * 应用级配置
	 * @param string $dir
	 * @param string $type
	 * $param string $sub
	 * @param string $module
	 */
	public function loadApplicationConfig($dir, $type, $sub = null, $module = null)
	{
		$this->loadModuleConfig($dir, $type, $sub, $module);
		$this->loadActionConfig(ocDir(array($dir, $type, $sub)));
	}

	/**
	 * 加载配置
	 * @param string $path
	 */
	public function loadControlConfig($path)
	{
		$CONF = &$this->_properties;
		$path = ocForceArray($path);

		foreach ($path as $value) {
			if ($files = scandir($value)) {
				$config = $CONF;
				foreach ($files as $file) {
					if ($file == '.' or $file == '..') continue;
					$fileType = pathinfo($file, PATHINFO_EXTENSION);
					$file = $value . OC_DIR_SEP . $file;
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
	 * @param mxied $name
	 * @param mixed $value
	 */
	public function set($name, $value)
	{
		ocSet($this->_properties, $name, $value);
	}

    /**
     * 获取配置
     * @param string $key
     * @return array|bool|mixed|null
     */
    public function get($key = null)
    {
        if (func_num_args()) {
            if (ocKeyExists($key, $this->_properties)) {
                return ocGet($key, $this->_properties);
            }
            return $this->getDefault($key);
        }

        return $this->_properties;
    }

    /**
     * 删除配置
     * @param string $key
     * @param mixed $value
     */
    public function delete($key)
    {
        ocDel($this->_properties, $key);
    }

	/**
	 * 获取默认配置
	 * @param string $key
	 * @return array|bool|mixed|null
	 */
	public function getDefault($key = null)
	{
		if (func_num_args()) {
			return ocGet($key, $this->_ocData);
		}

		return $this->_ocData;
	}

	/**
	 * 检查配置键名是否存在
	 * @param string $name
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