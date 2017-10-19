<?php
/**
 * Created by PhpStorm.
 * User: BORUI-DIY
 * Date: 2017/6/25 0025
 * Time: 下午 1:50
 */
namespace Ocara;
use Ocara\Interfaces\ServiceProvider as ServiceProviderInterface;

abstract class BootstrapBase extends ServiceProvider implements ServiceProviderInterface
{
    /**
     * 获取默认服务提供器
     * @return string
     */
    public function getServiceProvider()
    {
        return new \Ocara\Service\Provider\Defaults();
    }

    /**
     * 获取默认服务提供器
     * @return string
     */
    public function getContainer()
    {
        return new \Ocara\Container();
    }
}