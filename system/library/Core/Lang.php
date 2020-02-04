<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 语言配置控制类Lang
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Base;
use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class Lang extends Base
{
	protected $frameworkConfig = array();
	protected $data = array();

    /**
     * 初始化
     * Lang constructor.
     */
	public function __construct()
	{
		if  (empty($this->frameworkConfig)){
			$file = ocService()->app->getLanguage() . '.php';
			$path = OC_SYS . 'data/languages/' . $file;

			if (file_exists($path)) {
				$lang = include($path);
				if ($lang) {
					$this->frameworkConfig = ocForceArray($lang);
				}
			}
		}
		$this->load(ocPath('lang', ocService()->app->getLanguage()));
	}

    /**
     * 加载模块配置
     * @param $route
     * @param null $rootPath
     */
    public function loadModuleConfig($route, $rootPath = null)
    {
        $subPath = sprintf(
            'lang/%s/control/',
            ocService()->app->getLanguage()
        );
        $path = $this->getConfigPath($route, $subPath, $rootPath);
        $this->load($path);
    }

    /**
     * 加载控制器动作配置
     * @param array $route
     * @param null $rootPath
     */
    public function loadControllerConfig($route = array(), $rootPath = null)
    {
        $subPath = sprintf(
            'lang/%s/control/%s/',
            ocService()->app->getLanguage(),
            $route['controller']
        );

        $path = $this->getConfigPath($route, $subPath, $rootPath);
        if (is_dir($path)) {
            $this->load($path);
        }
    }

    /**
     * 加载控制器动作配置
     * @param array $route
     * @param null $rootPath
     */
    public function loadActionConfig($route = array(), $rootPath = null)
    {
        $subPath = sprintf(
            'lang/%s/control/%s/',
            ocService()->app->getLanguage(),
            $route['controller']
        );

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
                $path = ocPath('modules', $subPath);
            } else {
                $path = ocPath('application', 'resource/' . $subPath);
            }
        }

        return $path;
    }

    /**
     * 加载语言配置
     * @param $paths
     */
	public function load($paths)
	{
	    if ($paths) {
            $paths = ocForceArray($paths);
            $data = array($this->data);

            foreach ($paths as $path) {
                if (is_dir($path)) {
                    $files = scandir($path);
                    foreach ($files as $file) {
                        if ($file == '.' || $file == '..') continue;
                        $fileType = pathinfo($file, PATHINFO_EXTENSION);
                        if (is_file($file = $path . OC_DIR_SEP . $file) && $fileType == 'php') {
                            $row = @include($file);
                            if ($row && is_array($row)) {
                                $data[] = $row;
                            }
                        }
                    }
                }
            }

            $data = call_user_func_array('array_merge', $data);
            $this->data = $data;
        }
	}

    /**
     * 获取语言配置
     * @param string|array $key
     * @param array $params
     * @return array|null
     * @throws Exception
     */
    public function get($key = null, array $params = array())
    {
		if (func_num_args()) {
			if (ocKeyExists($key, $this->data)) {
                $value =  ocGetLanguage($this->data, $key, $params);
			} else {
                $value = $this->getDefault($key, $params);
            }
			return $value;
		}

		return $this->data;
	}

    /**
     * 获取默认语言
     * @param string $key
     * @param array $params
     * @return array
     */
	public function getDefault($key = null, array $params = array())
	{
		if (func_num_args()) {
			return ocGetLanguage($this->frameworkConfig, $key, $params);
		}

		return $this->frameworkConfig;
	}

    /**
     * 设置语言
     * @param $key
     * @param null $value
     */
	public function set($key, $value = null)
	{
		ocSet($this->data, $key, $value);
	}

    /**
     * 检查语言键名是否存在
     * @param string|array $key
     * @return array|bool|mixed|null
     */
	public function has($key = null)
	{
		return ocKeyExists($key, $this->data);
	}

    /**
     * 删除语言配置
     * @param string|array $key
     * @return array|null
     */
	public function delete($key)
	{
		return ocDel($this->data, $key);
	}
}