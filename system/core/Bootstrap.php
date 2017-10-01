<?php
/**
 * Created by PhpStorm.
 * User: BORUI-DIY
 * Date: 2017/6/25 0025
 * Time: 下午 1:50
 */
namespace Ocara;

class Bootstrap extends Base
{
    public $services = array();

    /**
     * 初始化
     */
    public function init()
    {
        self::loadSingleClass();

        $services = $this->services;
        foreach ($services as $name => $method) {
            self::$container->bind($name, $this->$method());
        }

        self::_checkEnvironment();
        if (!ocFileExists(OC_ROOT . '.htaccess')) {
            self::createHtaccess();
        }
    }

    /**
     * 访问控制器
     * @param $route
     * @throws Exception
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

        Ocara::checkRouteAccess($route);
        Ocara::boot($route);
    }

    /**
     * 加载单例模式类
     */
    private static function loadSingleClass()
    {
        $classes = ocConfig('SYSTEM_SERVICE_CLASS');

        foreach ($classes as $class => $namespace) {
            $name = lcfirst($class);
            self::$container->bindSingleton($name, function() use($namespace) {
                $file = strtr($namespace, ocConfig('AUTOLOAD_MAP')) . '.php';
                ocImport($file);
                if (method_exists($namespace, 'getInstance')) {
                    return $namespace::getInstance();
                } else {
                    return new $namespace();
                }
            });
        }
    }

    /**
     * 环境检测
     */
    private static function _checkEnvironment()
    {
        date_default_timezone_set(ocConfig('DATE_FORMAT.timezone', 'PRC'));
        if (!@ini_get('short_open_tag')) {
            Error::show('need_short_open_tag');
        }
        if (empty($_SERVER['REQUEST_METHOD'])) {
            $_SERVER['REQUEST_METHOD'] = 'GET';
        }
    }

    /**
     * 获取路由信息
     */
    public static function getRouteInfo()
    {
        $_GET = Url::parseGet();
        $route = self::$container->route;
        list($module, $controller, $action) = $route::parseRouteInfo();
        $route = compact('module', 'controller', 'action');
        return $route;
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