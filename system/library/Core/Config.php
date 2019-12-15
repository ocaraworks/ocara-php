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
	protected $environment;
	protected $frameworkConfig = array();
	protected $data = array();

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
            $this->frameworkConfig = $OC_CONF;
        } else {
            throw new Exception('Lost config : $OC_CONF.');
        }
	}

    /**
     * 获取系统环境
     */
	public function getEnvironment()
    {
        if (!isset($this->environment)) {
            $environmentResource = ocConfig('RESOURCE.resource.env.get_env');
            if ($environmentResource) {
                $this->environment = call_user_func_array(array($environmentResource, 'handle')) ?: OC_EMPTY;
            }
        }

        return $this->environment;
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

        if (empty($this->data)) {
            throw new Exception('Lost config : $CONF.');
        }
    }

    /**
     * 获取环境配置
     * @param null $path
     */
    public function loadEnvironmentConfig($path = null)
    {
        $path = ($path ?: ocPath('config')) . 'env';
        if (is_dir($path)) {
            $this->load($path);
        }
    }

    /**
     * 加载模块配置
     * @param string $route
     * @param string $rootPath
     */
    public function loadModuleConfig($route, $rootPath = null)
    {
        $subPath = 'config/';
        $path = $this->getConfigPath($route, $subPath, $rootPath);
        if (is_dir($path)) {
            $this->load($path);
            $this->loadEnvironmentConfig($path);
        }
    }

    /**
     * 加载控制器动作配置
     * @param array $route
     * @param string $rootPath
     */
	public function loadControllerConfig($route = array(), $rootPath = null)
	{
        $subPath = sprintf('config/control/%s/', $route['controller']);
        $path = $this->getConfigPath($route, $subPath, $rootPath);

        if (is_dir($path)) {
            $this->load($path);
            $this->loadEnvironmentConfig($path);
        }
	}

    /**
     * 加载控制器动作配置
     * @param array $route
     * @param string $rootPath
     */
    public function loadActionConfig($route = array(), $rootPath = null)
    {
        $subPath = sprintf('config/control/%s/', $route['controller']);
        $path = $this->getConfigPath($route, $subPath, $rootPath);

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
     */
	protected function getConfigPath($route, $subPath, $rootPath)
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
            $config = array($this->data);
            foreach ($paths as $path) {
                if (is_dir($path)) {
                    $files = scandir($path);
                    asort($files);
                    foreach ($files as $file) {
                        if ($file == '.' || $file == '..') continue;
                        $file = $path . OC_DIR_SEP . $file;
                        if (is_file($file)) {
                            $fileType = pathinfo($file, PATHINFO_EXTENSION);
                            if ($fileType == 'php') {
                                $content = include($file);
                                if (is_array($content)) {
                                    $config[] = $content;
                                }
                            }
                        }
                    }
                }
            }

            $config = call_user_func_array('array_merge', $config);
            $this->data = $config;
        }
    }

    /**
     * 设置配置
     * @param $key
     * @param $value
     */
	public function set($key, $value)
	{
		ocSet($this->data, $key, $value);
	}

    /**
     * 获取配置
     * @param null $key
     * @param null $default
     * @return array|bool|mixed|null
     */
    public function get($key = null, $default = null)
    {
        if (isset($key)) {
            $result = null;
            if (ocKeyExists($key, $this->data)) {
                $result = ocGet($key, $this->data);
            } elseif (ocKeyExists($key, $this->frameworkConfig)) {
                $result = $this->getDefault($key);
            }
            $result = $result ? : (func_num_args() >= 2 ? $default: $result);
            return $result;
        }

        return $this->data;
    }

    /**
     * 存在配置时返回值数组
     * @param string|array $key
     * @return array|bool|null
     */
    public function arrayGet($key){
        if (($result = ocCheckKey($key, $this->data))
            || ($result = ocCheckKey($key, $this->frameworkConfig))
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
        ocDel($this->data, $key);
    }

    /**
     * 获取默认配置
     * @param string|array $key
     * @return array|bool|mixed|null
     */
	public function getDefault($key = null)
	{
		if (isset($key)) {
			return ocGet($key, $this->frameworkConfig);
		}

		return $this->frameworkConfig;
	}

    /**
     * 检查配置键名是否存在
     * @param string|array $key
     * @return bool
     */
	public function has($key)
	{
		return ocKeyExists($key, $this->data) || ocKeyExists($key, $this->frameworkConfig);
	}
}