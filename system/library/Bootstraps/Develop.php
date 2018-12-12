<?php
/**
 * Created by PhpStorm.
 * User: BORUI-DIY
 * Date: 2017/6/25 0025
 * Time: 下午 1:50
 */
namespace Ocara\Bootstraps;

use Ocara\Interfaces\Bootstrap as BootstrapInterface;
use Ocara\Core\BootstrapBase;
use Ocara\Core\Develop as DevelopBootstrap;
use Ocara\Dispatchers\Develop as DevelopDispatcher;

class Develop extends BootstrapBase implements BootstrapInterface
{
    public static $config;

    /**
     * 运行访问控制器
     * @param array|string $route
     * @return mixed
     * @throws \Ocara\Exceptions\Exception
     */
    public function start($route)
    {
        if (OC_SYS_MODEL != 'develop') {
            ocService()->error->show('unallowed_develop');
        }

        $resourcePath = OC_SYS . 'develop/resource';

        $service = ocService();
        $service->config->loadModuleConfig($route, $resourcePath);
        $service->lang->loadModuleConfig($route, $resourcePath);

        if (empty($route['controller'])) {
            $route['controller'] = ocConfig('DEFAULT_CONTROLLER');
        }

        $service->config->loadActionConfig($route, $resourcePath);
        $service->lang->loadActionConfig($route, $resourcePath);

        session_start();
        $this->event(self::EVENT_BEFORE_RUN)
             ->fire();

        define('OC_DEV_DIR', $resourcePath . OC_DIR_SEP);

        $dispatcher = new DevelopDispatcher();
        $service->setService('dispatcher', $dispatcher);
        $dispatcher->dispatch($route);

        $service->response->sendHeaders();
        return $service->response->send();
    }
}