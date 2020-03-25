<?php
/**
 * pass目录中控制器动作引入类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use Ocara\Exceptions\Exception;

final class Invoke
{
    /**
     * @var $instance 实例
     */
    private static $instance;

    /**
     * 单例模式引用
     * @return $this
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 目录分隔符替换
     * @param $path
     * @return mixed
     */
    protected function getCommPath($path)
    {
        return str_replace(DIRECTORY_SEPARATOR, '/', $path);
    }

    /**
     * 初始化
     * @param null $bootstrap
     * @return $this
     * @throws \Exception
     */
    public function init($bootstrap = null)
    {
        defined('OC_EXECUTE_START_TIME') OR define('OC_EXECUTE_START_TIME', microtime(true));
        defined('OC_PATH') OR define('OC_PATH', $this->getCommPath(realpath(dirname(dirname(dirname(__DIR__))))) . '/');
        defined('OC_INVOKE') OR define('OC_INVOKE', true);

        if (!is_file($path = OC_PATH . 'system/library/Core/Ocara.php')) {
            throw new \Exception('Lost ocara file!');
        }

        include_once($path);
        if (!class_exists('\Ocara\Core\Ocara')) {
            throw new \Exception('Lost Ocara class!');
        }

        Ocara::getInstance();
        ocContainer()->app->bootstrap($bootstrap);
        return $this;
    }

    /**
     * 运行路由
     * @param $route
     * @param array $params
     * @param string $requestMethod
     * @return mixed
     * @throws Exception
     */
    public function run($route, $params = array(), $requestMethod = 'GET')
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        if ($params) {
            if ($requestMethod == 'GET') {
                $_GET = array_merge($_GET, $params);
            } else {
                $_POST = array_merge($_POST, $params);
                $_SERVER['REQUEST_METHOD'] = 'POST';
            }
        }

        if (empty($bootstrap)) {
            $bootstrap = defined('OC_BOOTSTRAP') ? OC_BOOTSTRAP : 'Ocara\Bootstraps\Common';
        }

        $application = ocContainer()->app;
        $application->bootstrap($bootstrap);
        $route = $application->formatRoute($route);

        $application->setRoute($route);
        $result = $application->run($route);
        return $result;
    }
}
