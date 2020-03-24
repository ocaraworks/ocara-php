<?php
/**
 * 服务组件基类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use \ReflectionClass;
use Ocara\Exceptions\Exception;

class ServiceBase extends Base
{
    private static $lang = null;
    private $error;

    /**
     * 加载语言文件
     * @param $filePath
     * @param string $languageClass
     */
    public static function loadLanguage($filePath, $languageClass = null)
    {
        $parentPath = ocCommPath(dirname($filePath));
        $subPath = str_replace($parentPath, '', ocCommPath($filePath));

        $path = $parentPath . '/Languages/'
            . ucfirst(ocService()->app->getLanguage())
            . $subPath;

        if ($languageClass) {
            $path = dirname($path) . OC_DIR_SEP . $languageClass . '.php';
        }

        if (ocFileExists($path)) {
            $config = include($path);
            if ($config && is_array($config)) {
                self::$lang[self::getClass()] = $config;
            }
        }
    }

    /**
     * 获取语言配置信息
     * @param $key
     * @param array $params
     * @param null $languageClass
     * @return array
     * @throws Exception
     */
    public static function getLanguage($key, array $params = array(), $languageClass = null)
    {
        $class = self::getClass();

        if (!isset(self::$lang[$class])) {
            try {
                $reflection = new ReflectionClass($class);
                $fileName = $reflection->getFileName();
            } catch (\Exception $exception) {
                throw new Exception($exception->getMessage(), $exception->getCode());
            }
            self::loadLanguage($fileName, $languageClass);
        }

        if ($class && array_key_exists($class, self::$lang)) {
            $languages = self::$lang[$class];
        } else {
            $languages = array();
        }

        return ocGetLanguage($languages, $key, $params);
    }

    /**
     * 类文件是否存在
     * @param string $classFile
     * @return array
     */
    public static function classFileExists($classFile)
    {
        if ($classFile) {
            if (ocFileExists($path = OC_LIB . $classFile)) {
                return array($path, 'Ocara' . OC_NS_SEP);
            }
            if (ocFileExists($path = OC_EXT . $classFile)) {
                return array($path, 'Ocara\Core\Extension' . OC_NS_SEP);
            }
            if (ocFileExists($path = ocPath('support', $classFile))) {
                return array($path, OC_NS_SEP);
            }
        }

        return array();
    }

    /**
     * 获取语言内容
     * @param $key
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public static function getMessage($key, array $params = array())
    {
        $language = self::getLanguage($key, $params);
        return $language['message'];
    }

    /**
     * 显示错误信息
     * @param $error
     * @param array $params
     * @param null $languageClass
     * @throws Exception
     */
    public function showError($error, array $params = array(), $languageClass = null)
    {
        $error = self::getLanguage($error, $params, $languageClass);

        throw new Exception(
            str_ireplace('%s', OC_EMPTY, $error['message']), $error['code']
        );
    }

    /**
     * 错误是否存在
     */
    public function errorExists()
    {
        return empty($this->error) ? false : true;
    }

    /**
     * 获取错误信息
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 设置错误信息
     * @param $message
     * @param array $params
     * @return bool
     * @throws Exception
     */
    protected function setError($message, array $params = array())
    {
        $this->error = self::getLanguage($message, $params);
        return false;
    }
}