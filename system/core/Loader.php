<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   AJAX请求处理类Ajax
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

class Loader extends Base
{

    /**
     * 自动加载类
     * @param string $class
     * @return bool|mixed
     * @throws Exception
     */
    public static function autoload($class)
    {
        $newClass = trim($class, OC_NS_SEP);

        if (strstr($newClass, OC_NS_SEP)) {
            $filePath = strtr($newClass, ocConfig('AUTOLOAD_MAP'));
            if ($filePath == $class) {
                $filePath = strtr($newClass, ocConfig('APP_AUTOLOAD_MAP'));
            }
            if ($filePath == $newClass) {
                $filePath = OC_ROOT . 'service/library/' . $newClass;
            }
            $filePath .= '.php';
        } else {
            $filePath = OC_ROOT . 'service/library/' . $newClass . '.php';
        }

        $filePath = ocCommPath($filePath);
        if (ocFileExists($filePath)) {
            include($filePath);
            if (class_exists($newClass, false)) {
                if (method_exists($newClass, 'loadLanguage')) {
                    $newClass::loadLanguage($filePath);
                }
                return true;
            }
            if (interface_exists($newClass, false)) {
                return true;
            }
        }

        $autoloads = spl_autoload_functions();
        foreach ($autoloads as $func) {
            if (is_string($func)) {
                call_user_func_array($func, array($class));
            } elseif (is_array($func)) {
                if (reset($func) === __CLASS__) continue;
                call_user_func_array($func, array($class));
            } else {
                continue;
            }
            if (class_exists($class, false) || interface_exists($newClass, false)) {
                return true;
            }
        }

        Ocara::services()->error->show('not_exists_class', array($class));
    }
}