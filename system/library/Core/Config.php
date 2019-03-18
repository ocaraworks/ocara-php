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
use \Exception;

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
        $path = OC_SYS . 'data/config.php';
		if (!file_exists($path)) {
			throw new Exception('Lost ocara config file: config.php.');
		}

        $OC_CONF = include ($path);

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
     * @param $route
     * @param string $rootPath
     * @throws \Ocara\Exceptions\Exception
     */
    public function loadModuleConfig($route, $rootPath = null)
    {
        $subPath = 'config/control/';
        $path = $this->_getConfigPath($route, $subPath, $rootPath);
        $this->load($path);
    }

    /**
     * 加载控制器动作配置
     * @param array $route
     * @param string $rootPath
     * @throws \Ocara\Exceptions\Exception
     */
	public function loadControllerConfig($route = array(), $rootPath = null)
	{
        $subPath = sprintf('config/control/%s/', $route['controller']);
        $path = $this->_getConfigPath($route, $subPath, $rootPath);

        if (is_dir($path)) {
            $this->load($path);
        }
	}

    /**
     * 加载控制器动作配置
     * @param array $route
     * @param string $rootPath
     * @throws \Ocara\Exceptions\Exception
     */
    public function loadActionConfig($route = array(), $rootPath = null)
    {
        $subPath = sprintf('config/control/%s/', $route['controller']);
        $path = $this->_getConfigPath($route, $subPath, $rootPath);

        if (is_dir($path)) {
            if ($route['action'] && is_dir($path = $path . OC_DIR_SEP . $route['action'])) {
                $this->load($path);
            }
        }
    }

    /**
     * 获取配置文件路径
     * @param $route
     * @param $subPath
     * @param $rootPath
     * @return mixed|string
     * @throws \Ocara\Exceptions\Exception
     */
	protected function _getConfigPath($route, $subPath, $rootPath)
    {
        if ($route['module']) {
            $subPath = $route['module'] . '/privates/' . $subPath;
        }

        if ($rootPath) {
            $path = $rootPath . OC_DIR_SEP . $subPath;
        } else {
            if ($route['module']) {
                if (OC_MODULE_PATH) {
                    $path = ocDir(array(OC_MODULE_PATH, $subPath));
                } else {
                    $path = ocPath('modules', $subPath);
                }
            } else {
                $path = ocPath('application', 'resource/' . $subPath);
            }
        }

        return $path;
    }

	/**
	 * 加载配置
	 * @param string|array $paths
	 */
	public function load($paths)
    {
        if ($paths) {
            $paths = ocForceArray($paths);
            $config = array($this->_properties);
            foreach ($paths as $path) {
                if (is_dir($path)) {
                    $files = scandir($path);
                    foreach ($files as $file) {
                        if ($file == '.' || $file == '..') continue;
                        $fileType = pathinfo($file, PATHINFO_EXTENSION);
                        $file = $path . OC_DIR_SEP . $file;
                        if (is_file($file) && $fileType == 'php') {
                            $content = include($file);
                            if (is_array($content)) {
                                $config[] = $content;
                            }
                        }
                    }
                }
            }

            $config = call_user_func_array('array_merge', $config);
            $this->_properties = $config;
        }
    }

    /**
     * 设置配置
     * @param $key
     * @param $value
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
     * @param string|array $key
     * @return array|bool|null
     */
    public function arrayGet($key){
        if (($result = ocCheckKey($key, $this->_properties))
            || ($result = ocCheckKey($key, $this->_frameworkConfig))
        ) {
            return $result;
        }
        return array();
    }

    /**
     * 删除配置
     * @param string|array $key
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
     * @return bool
     */
	public function has($key)
	{
		return ocKeyExists($key, $this->_properties) || ocKeyExists($key, $this->_frameworkConfig);
	}
}