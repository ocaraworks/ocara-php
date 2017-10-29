<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 默认服务提供器Defaults
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Provider;
use Ocara\Ocara;
use Ocara\ServiceProvider;

class Defaults extends ServiceProvider
{
    public function register()
    {
        $classes = ocConfig('SYSTEM_SERVICE_CLASS');
        $container = Ocara::container();

        foreach ($classes as $class => $namespace) {
            $name = lcfirst($class);
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
    }
}