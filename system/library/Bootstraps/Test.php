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
     * echo $urlDir;die;
     * @param array $route
     * @return mixed|void
     */
    public function start($route = array())
    {
        $service = ocService();

        if (!empty($route['module'])) {
            $service->config->loadModuleConfig($route);
            $service->lang->loadModuleConfig($route);
        }
    }
}