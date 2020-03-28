<?php
/**
 * 配置处理类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use \ReflectionException;
use Ocara\Exceptions\Exception;

class Config extends Base
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

    const EVENT_GET_ENVIRONMENT = 'getEnvironment';

    /**
     * 初始化
     * Config constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $path = OC_SYS . 'data/config.php';
        if (!file_exists($path)) {
            throw new \Exception('Lost ocara config file: config.php.');
        }

        $OC_CONF = include($path);

        if (isset($OC_CONF)) {
            $this->frameworkConfig = $OC_CONF;
        } else {
            throw new \Exception('Lost config : $OC_CONF.');
        }
    }

    /**
     * @throws Exception
     */
    public function registerEvents()
    {
        $this->event(self::EVENT_GET_ENVIRONMENT)
            ->resource()
            ->append(ocConfig('RESOURCE.env.getEnv', null));
    }

    /**
     * 获取系统环境
     * @return array|mixed
     * @throws Exception
     * @throws ReflectionException
     */
    public function getEnvironment()
    {
        if (!isset($this->environment)) {
            if ($this->event(self::EVENT_GET_ENVIRONMENT)->has()) {
                $environment = $this->fire(self::EVENT_GET_ENVIRONMENT);
                if ($environment) {
                    $this->environment = $environment;
                }
            }
        }
        return $this->environment;
    }

    /**
     * 加载全局配置
     * @throws \Exception
     */
    public function loadGlobalConfig()
    {
        $path = ocPath('config');
        if (is_dir($path)) {
            $this->load($path);
        }

        if (empty($this->data)) {
            throw new \Exception('Lost config : $CONF.');
        }
    }

    /**
     * 获取环境配置
     * @param string $path
     * @throws Exception
     */
    public function loadEnvironmentConfig($path = null)
    {
        if ($this->environment) {
            $filePath = ($path ?: ocPath('config')) . 'env/' . $this->environment . '.php';
            if (is_file($filePath)) {
                $content = include($filePath);
                if (is_array($content)) {
                    $this->data = array_merge($this->data, $content);
                }
            }
        }
    }

    /**
     * 加载模块配置
     * @param $route
     * @param string $rootPath
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
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
     * @param string $route
     * @param string $subPath
     * @param string $rootPath
     * @return array|mixed|object|string|void|null
     * @throws Exception
     */
    protected function getConfigPath($route, $subPath, $rootPath)
    {
        if ($route['module']) {
            $subPath = $route['module'] . OC_NS_SEP . $subPath;
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
     * @param string $key
     * @param mixed $value
     * @throws Exception
     */
    public function set($key, $value)
    {
        ocSet($this->data, $key, $value);
    }

    /**
     * 获取配置
     * @param string $key
     * @param mixed $default
     * @return array|mixed|null
     * @throws Exception
     */
    public function get($key = null, $default = null)
    {
        if (isset($key)) {
            $result = ocGet($key, $this->data);
            if (!$result) {
                $result = ocGet($key, $this->frameworkConfig);
            }
            $result = $result ?: (func_num_args() >= 2 ? $default : $result);
            return $result;
        }

        return $this->data;
    }

    /**
     * 存在配置时返回值数组
     * @param string $key
     * @return array|bool
     */
    public function arrayGet($key)
    {
        if (($result = ocCheckKey($key, $this->data))
            || ($result = ocCheckKey($key, $this->frameworkConfig))
        ) {
            return $result;
        }
        return array();
    }

    /**
     * 删除配置
     * @param string $key
     */
    public function delete($key)
    {
        ocDel($this->data, $key);
    }

    /**
     * 获取默认配置
     * @param string $key
     * @return array|mixed|null
     * @throws Exception
     */
    public function getDefault($key = null)
    {
        if (func_num_args()) {
            return ocGet($key, $this->frameworkConfig);
        }

        return $this->frameworkConfig;
    }

    /**
     * 检查配置键名是否存在
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return ocKeyExists($key, $this->data) || ocKeyExists($key, $this->frameworkConfig);
    }
}