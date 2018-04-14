<?php
/**
 * Created by PhpStorm.
 * User: BORUI-DIY
 * Date: 2017/6/25 0025
 * Time: 下午 1:50
 */
namespace Ocara;
use Ocara\Interfaces\Bootstrap as BootstrapInterface;
use \Ocara\ExceptionHandler;

class Bootstrap extends BootstrapBase implements BootstrapInterface
{
    /**
     * 初始化
     */
    public function init()
    {
        date_default_timezone_set(ocConfig('DATE_FORMAT.timezone', 'PRC'));
        set_exception_handler(array(Ocara::services()->exceptionHandler, 'run'));

        Ocara::getInstance()
            ->event('die')
            ->append(ocConfig('EVENT.oc_die', null));

        Ocara::getInstance()
            ->bindEvents(ocConfig('EVENT.log', Ocara::services()->log));

        if (!@ini_get('short_open_tag')) {
            Ocara::services()->error->show('need_short_open_tag');
        }

        if (!ocFileExists(OC_ROOT . '.htaccess')) {
            self::createHtaccess();
        }

        $this->event('beforeRun')
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
                Ocara::services()->error->show('unallowed_develop');
            }
        }

        $this->event('beforeRun')->fire(array($route));
        self::run($route);
    }

    /**
     * 生成伪静态文件
     * @param bool $moreContent
     * @throws Exception
     */
    public static function createHtaccess($moreContent = false)
    {
        $file = OC_ROOT . '.htaccess';
        $htaccess = ocImport(OC_SYS . 'data/rewrite/apache.php');

        if (empty($htaccess)) {
            Ocara::services()->error->show('no_rewrite_default_file');
        }

        if (is_writeable(OC_ROOT)) {
            $htaccess = sprintf($htaccess, $moreContent);
            ocWrite($file, $htaccess);
        } else {
            Ocara::services()->error->show('not_writeable_htaccess');
        }
    }
}