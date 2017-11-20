<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 默认服务提供器Main
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Provider;
use Ocara\Ocara;
use Ocara\ServiceProvider;

class Main extends ServiceProvider
{
    public function register()
    {
        $container = Ocara::container();

        $classes = ocConfig('SYSTEM_SINGLETON_SERVICE_CLASS');
        foreach ($classes as $name => $namespace) {
            $container->bindSingleton($name, function() use($namespace) {
                $file = strtr($namespace, ocConfig('AUTOLOAD_MAP')) . '.php';
                ocImport($file);
                if (method_exists($namespace, 'getInstance')) {
                    return $namespace::getInstance();
                } else {
                    return new $namespace();
                }
            });
        }

        $classes = ocConfig('SYSTEM_SERVICE_CLASS');
        foreach ($classes as $name => $namespace) {
            $container->bind($name, function() use($namespace) {
                $file = strtr($namespace, ocConfig('AUTOLOAD_MAP')) . '.php';
                ocImport($file);
                if (method_exists($namespace, 'getInstance')) {
                    return $namespace::getInstance();
                } else {
                    return new $namespace();
                }
            });
        }
    }
}