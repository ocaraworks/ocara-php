<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 配置控制类Config
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Basis;
use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class Config extends Basis
{
	/**
	 * 开关配置
	 */
	const YES = 1;
	const NO = 0;

	/**
	 * 数据变量
	 */
	protected $_frameworkConfig = array();

	/**
	 * 初始化
     *
	 */
	public function __construct()
	{
        $path = OC_SYS . 'data/default.php';
		if (!file_exists($path)) {
			throw new Exception('Lost ocara config file: default.php.');
		}

		include ($path);

		if (isset($OC_CONF)) {
            $this->_frameworkConfig = $OC_CONF;
        } else {
            throw new Exception('Lost config : $OC_CONF.');
        }
	}

    /**
     * 加载全局配置
     * @throws Exception
     */
	public function loadGlobalConfig()
    {
        $path = ocPath('config');
        if (is_dir($path)) {
            $this->load($path);
        }

        if (empty($this->_properties)) {
            throw new Exception('Lost config : $CONF.');
        }

        ocService()->app->setLanguage($this->get('LANGUAGE', 'zh_cn'));
    }

	/**
	 * 加载模块配置
	 * @param string $dir
	 */
	public function loadModuleConfig($route = array())
	{
        extract($route);
        $path = ocPath('modules', $module . '/private/config/');
        $paths = array();

        if (is_dir($path)) {
            $paths[] = $path;
            if ($controller && is_dir($path = $path . OC_DIR_SEP . $controller)) {
                $paths[] = $path;
                if ($action && is_dir($path = $path . OC_DIR_SEP . $action)) {
                    $paths[] = $path;
                }
            }
            $this->load($paths);
        }
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
     * @param null $key
     * @param null $default
     * @return array|bool|mixed|null|自定义属性
     * @throws Exception
     */
    public function get($key = null, $default = null, $existsWrap = false)
    {
        if (isset($key)) {
            $result = null;
            if (ocKeyExists($key, $this->_properties)) {
                $result = ocGet($key, $this->_properties);
            } elseif (ocKeyExists($key, $this->_frameworkConfig)) {
                $result = $this->getDefault($key);
            }
            $result = $result ? : (func_num_args() >= 2 ? $default: $result);
            return $result;
        }

        return $this->_properties;
    }

    /**
     * 存在配置时返回值数组
     * @return array|bool|null
     */
    public function arrayGet($key){
        if (($result = ocCheckKey(false, $key, $this->_properties, true))
            || ($result = ocCheckKey(false, $key, $this->_frameworkConfig, true))
        ) {
            return $result;
        }
        return array();
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
			return ocGet($key, $this->_frameworkConfig);
		}

		return $this->_frameworkConfig;
	}

	/**
	 * 检查配置键名是否存在
	 * @param string|array $key
	 * @return array|bool|mixed|null
	 */
	public function has($key)
	{
		return ocKeyExists($key, $this->_properties) || ocKeyExists($key, $this->_frameworkConfig);
	}
}