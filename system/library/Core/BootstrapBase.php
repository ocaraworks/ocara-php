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
    const EVENT_DIE = 'die';
    const EVENT_BEFORE_DISPATCH = 'beforeDispatch';

    /**
     * 注册事件
     * @throws \Ocara\Exceptions\Exception
     */
    public function registerEvents()
    {
        $this->event(self::EVENT_DIE)
            ->append(ocConfig('EVENT.oc_die', null));

        $this->bindEvents(ocConfig('EVENT.log', ocService()->log));

        $this->event(self::EVENT_BEFORE_DISPATCH)
             ->append(ocConfig('EVENT.action.before_run', null))
             ->append(ocConfig('EVENT.auth.check', null));
    }

    /**
     * 初始化
     */
    public function init()
    {
        if (empty($_SERVER['REQUEST_METHOD'])) {
            $_SERVER['REQUEST_METHOD'] = 'GET';
        }

        date_default_timezone_set(ocConfig('DATE_FORMAT.timezone', 'PRC'));

        if (!@ini_get('short_open_tag')) {
            ocService()->error->show('need_short_open_tag');
        }
    }

    /**
     * 加载路由配置
     * @param array $route
     * @return mixed
     * @throws \Ocara\Exceptions\Exception
     */
    public function loadRouteConfig(array $route)
    {
        if (empty($route['module'])) {
            $route['module'] = OC_DEFAULT_MODULE;
        }

        $service = ocService();
        $service->config->loadModuleConfig($route);
        $service->lang->loadModuleConfig($route);

        if (empty($route['controller'])) {
            $route['controller'] = ocConfig('DEFAULT_CONTROLLER');
        }

        if (empty($route['action'])) {
            $route['action'] = ocConfig('DEFAULT_ACTION');
        }

        $service->app->setRoute($route);

        $service->config->loadControllerConfig($route);
        $service->config->loadActionConfig($route);
        $service->lang->loadControllerConfig($route);
        $service->lang->loadActionConfig($route);

        return $route;
    }
}