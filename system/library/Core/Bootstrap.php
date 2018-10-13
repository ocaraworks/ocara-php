<?php
/**
 * Created by PhpStorm.
 * User: BORUI-DIY
 * Date: 2017/6/25 0025
 * Time: 下午 1:50
 */
namespace Ocara\Core;

use Ocara\Interfaces\Bootstrap as BootstrapInterface;

class Bootstrap extends BootstrapBase implements BootstrapInterface
{
    const EVENT_DIE = 'die';
    const EVENT_BEFORE_RUN = 'beforeRun';

    /**
     * 初始化
     */
    public function init()
    {
        date_default_timezone_set(ocConfig('DATE_FORMAT.timezone', 'PRC'));
        set_exception_handler(array(ocService()->exceptionHandler, 'run'));

        $this->event(self::EVENT_DIE)
            ->append(ocConfig('EVENT.oc_die', null));

        $this->bindEvents(ocConfig('EVENT.log', ocService()->log));

        if (!@ini_get('short_open_tag')) {
            ocService()->error->show('need_short_open_tag');
        }

        if (!ocFileExists(OC_ROOT . '.htaccess')) {
            self::createHtaccess();
        }

        $this->event(self::EVENT_BEFORE_RUN)
             ->append(ocConfig('EVENT.action.before_run', null))
             ->append(ocConfig('EVENT.auth.check', null));
    }

    /**
     * 运行访问控制器
     * @param array|string $route
     * @throws Exception\Exception
     */
    public function start($route)
    {
        if ($route['module'] == OC_DEV_SIGN) {
            if (OC_SYS_MODEL == 'develop') {
                Develop::run();
            } else {
                ocService()->error->show('unallowed_develop');
            }
        }

        $this->event(self::EVENT_BEFORE_RUN)->fire(array($route));
        self::run($route);
    }

    /**
     * 生成伪静态文件
     * @param string $moreContent
     * @throws \Ocara\Exceptions\Exception
     */
    public static function createHtaccess($moreContent = OC_EMPTY)
    {
        $file = OC_ROOT . '.htaccess';
        $htaccess = ocImport(OC_SYS . 'data/rewrite/apache.php');

        if (empty($htaccess)) {
            ocService()->error->show('no_rewrite_default_file');
        }

        if (is_writeable(OC_ROOT)) {
            $htaccess = sprintf($htaccess, $moreContent);
            ocWrite($file, $htaccess);
        } else {
            ocService()->error->show('not_writeable_htaccess');
        }
    }
}