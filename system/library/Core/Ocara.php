<?php
/**
 * 框架引导类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use \ReflectionException;
use Ocara\Exceptions\Exception;

final class Ocara
{
    /**
     * @var $instance 实例
     * @var $info 框架信息
     */
    private static $instance;
    private static $info;
    private static $container;

    private function __clone()
    {
    }

    /**
     * Ocara constructor.
     */
    private function __construct()
    {
    }

    /**
     * 单例模式引用
     * @return Ocara
     * @throws Exception
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::initialize();
        }
        return self::$instance;
    }

    /**
     * 服务注册
     * @throws Exception
     */
    public static function initialize()
    {
        $path = realpath(dirname(dirname(dirname(__DIR__))));

        defined('OC_PATH') OR define('OC_PATH', str_replace("\\", '/', $path) . '/');
        defined('OC_EXECUTE_START_TIME') OR define('OC_EXECUTE_START_TIME', microtime(true));

        require_once(OC_PATH . 'system/functions/utility.php');
        require_once(OC_PATH . 'system/functions/common.php');
        require_once(OC_PATH . 'system/const/basic.php');
        require_once(OC_CORE . 'Basis.php');
        require_once(OC_CORE . 'Base.php');
        require_once(OC_CORE . 'Container.php');
        require_once(OC_CORE . 'Application.php');

        self::$container = new Container();
        self::$container->bindSingleton('app', 'Ocara\Core\Application');
    }

    /**
     * 获取默认容器
     * @return Container
     */
    public static function container()
    {
        return self::$container;
    }

    /**
     * 新建应用
     * @param string $moduleType
     * @throws Exception
     */
    public static function create($moduleType = 'common')
    {
        self::getInstance();
        include_once(OC_CORE . 'Application.php');
        ApplicationGenerator::create($moduleType);
    }

    /**
     * 运行路由
     * @param string $bootstrap
     * @return mixed
     * @throws Exception
     * @throws ReflectionException
     */
    public static function run($bootstrap = null)
    {
        self::getInstance();

        if (empty($bootstrap)) {
            $bootstrap = defined('OC_BOOTSTRAP') ? OC_BOOTSTRAP : 'Ocara\Bootstraps\Common';
        }

        $application = ocContainer()->app;
        $application->bootstrap($bootstrap);

        $route = $application->parseRoute();
        $result = $application->run($route);

        return $result;
    }

    /**
     * 开始应用
     * @param string $bootstrap
     * @throws Exception
     */
    public static function start($bootstrap = null)
    {
        self::getInstance();

        if (empty($bootstrap)) {
            $bootstrap = defined('OC_BOOTSTRAP') ? OC_BOOTSTRAP : 'Ocara\Bootstraps\Common';
        }

        ocContainer()->app
            ->bootstrap('Ocara\Bootstraps\Tests')
            ->start();
    }

    /**
     * 框架更新
     * @param array $params
     * @return bool
     * @throws Exception
     */
    public static function update(array $params = array())
    {
        ocImport(OC_APP_ROOT . 'pass/Update.php');
        $args = func_get_args();
        return class_exists('Ocara\Core\Update', false) ? Update::run($args) : false;
    }

    /**
     * 获取框架信息
     * @param string $key
     * @return array|mixed|null
     * @throws Exception
     */
    public static function getInfo($key = null)
    {
        if (is_null(self::$info)) {
            $path = OC_SYS . 'data/framework.php';
            if (ocFileExists($path)) {
                include($path);
            }
            if (isset($FRAMEWORK_INFO) && is_array($FRAMEWORK_INFO)) {
                self::$info = $FRAMEWORK_INFO;
            } else {
                self::$info = array();
            }
        }

        if (isset($key)) {
            return ocGet($key, self::$info);
        }

        return self::$info;
    }
}
