<?php
/**
 * Created by PhpStorm.
 * User: BORUI-DIY
 * Date: 2017/6/25 0025
 * Time: 下午 1:50
 */
namespace Ocara;
use Ocara\Interfaces\Bootstrap as BootstrapInterface;

class Bootstrap extends BootstrapBase implements BootstrapInterface
{
    /**
     * 初始化
     */
    public function init()
    {
        date_default_timezone_set(ocConfig('DATE_FORMAT.timezone', 'PRC'));

        Ocara::getInstance()
            ->event('die')
            ->append(ocConfig('EVENT.oc_die', null));

        Ocara::getInstance()
            ->bindEvents(ocConfig('EVENT.global_log', GlobalLog::getInstance()));

        if (!@ini_get('short_open_tag')) {
            Error::show('need_short_open_tag');
        }

        if (!ocFileExists(OC_ROOT . '.htaccess')) {
            self::createHtaccess();
        }

        $this->event('authCheck')
             ->append(ocConfig('EVENT.auth.check', null));
    }

    /**
     * 运行访问控制器
     */
    public function run($route)
    {
        if ($route['module'] == OC_DEV_SIGN) {
            if (OC_SYS_MODEL == 'develop') {
                Develop::run();
            } else {
                Error::show('unallowed_develop');
            }
        }

        $this->event('authCheck')->fire(array($route));
        Ocara::boot($route);
    }

    /**
     * 生成伪静态文件
     * @param bool $moreContent
     * @throws Exception
     */
    public static function createHtaccess($moreContent = false)
    {
        $htaccessFile = OC_ROOT . '.htaccess';
        $htaccess = ocImport(OC_SYS . 'data/rewrite/apache.php');

        if (empty($htaccess)) {
            Error::show('no_rewrite_default_file');
        }

        if (is_writeable(OC_ROOT)) {
            $htaccess = sprintf($htaccess, $moreContent);
            ocWrite($htaccessFile, $htaccess);
        } else {
            Error::show('not_writeable_htaccess');
        }
    }
}