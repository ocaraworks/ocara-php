<?php
/**
 * Created by PhpStorm.
 * User: BORUI-DIY
 * Date: 2017/6/25 0025
 * Time: 下午 1:50
 */
namespace Ocara\Core;

use Ocara\Core\Base;
use Ocara\Core\Container;
use Ocara\Providers\Main;

abstract class BootstrapBase extends Base
{
    /**
     * 获取默认服务提供器
     * @param \Ocara\Core\Container $container
     * @return Main
     */
    public function getServiceProvider(Container $container)
    {
        $provider = new Main(array(), $container);
        return $provider;
    }
}