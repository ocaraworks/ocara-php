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
    const EVENT_BEFORE_RUN = 'beforeRun';

    /**
     * 注册事件
     * @throws \Ocara\Exceptions\Exception
     */
    public function registerEvents()
    {
        $this->event(self::EVENT_DIE)
            ->append(ocConfig('EVENT.oc_die', null));

        $this->bindEvents(ocConfig('EVENT.log', ocService()->log));

        $this->event(self::EVENT_BEFORE_RUN)
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
}