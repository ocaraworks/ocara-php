<?php
/**
 * Created by PhpStorm.
 * User: BORUI-DIY
 * Date: 2017/6/25 0025
 * Time: 下午 1:50
 */
namespace Ocara\Bootstraps;

use Ocara\Exceptions\Exception;
use Ocara\Interfaces\Bootstrap as BootstrapInterface;
use Ocara\Core\BootstrapBase;

class Test extends BootstrapBase implements BootstrapInterface
{

    /**
     * 运行访问控制器
     * @param array|string $route
     * @param array $params
     * @param null $moduleNamespace
     * @return mixed
     */
    public function start($route = array())
    {
    }
}