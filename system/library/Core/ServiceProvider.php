<?php
/**
 * 服务提供器类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use Ocara\Interfaces\ServiceProvider as ServiceProviderInterface;
use Ocara\Exceptions\Exception;

/**
 * Class ServiceProvider
 * @property \Ocara\Core\Container $container
 * @property \Ocara\Core\CacheFactory $caches;
 * @property \Ocara\Core\DatabaseFactory $databases;
 * @property \Ocara\Core\Request $request
 * @property \Ocara\Core\Response $response
 * @property \Ocara\Core\Api $api
 * @property \Ocara\Core\Error $error
 * @property \Ocara\Dispatchers\Common $dispatcher
 * @property \Ocara\Core\Filter $filter
 * @property \Ocara\Core\Url $url
 * @property \Ocara\Core\Lang $lang
 * @property \Ocara\Core\Cookie $cookie
 * @property \Ocara\Core\Session $session
 * @property \Ocara\Core\Route $route
 * @property \Ocara\Core\Transaction $transaction
 * @property \Ocara\Service\File $file
 * @property \Ocara\Core\Font $font
 * @property \Ocara\Core\StaticPath $staticPath
 * @property \Ocara\Core\Globals $globals
 * @property \Ocara\Service\ErrorOutput $errorOutput
 * @property \Ocara\Core\FormToken $formToken
 * @property \Ocara\Core\Validator $validator
 * @property \Ocara\Service\Code $code
 * @property \Ocara\Service\Excel $excel
 * @property \Ocara\Service\Date $date
 * @property \Ocara\Service\Download $download
 * @property \Ocara\Service\StaticBuilder $staticBuilder
 * @property \Ocara\Service\Pager $pager
 * @property \Ocara\Core\Event $event
 * @property \Ocara\Core\Log $log
 * @property \Ocara\Core\Form $form
 * @property \Ocara\Core\Html $html
 * @property \Ocara\Core\FormManager $formManager
 * @property \Ocara\Service\Validate $validate
 * @property \Ocara\Service\Auth $auth
 * @property \Ocara\Service\Xml $xml
 * @property \Ocara\Service\FileCache $fileCache
 * @property \Ocara\Service\VerifyCode $verifyCode
 * @property \Ocara\Service\Mail $mail
 * @property \Ocara\Service\Ftp $ftp
 * @property \Ocara\Service\Image $image
 * @property \Ocara\Service\FileLog $fileLog
 * @property \Ocara\Service\Upload $upload
 * @property \Ocara\Views\Common|\Ocara\Views\Api $view
 */
class ServiceProvider extends Base implements ServiceProviderInterface
{
    protected $container;
    protected $services = array();
    private static $default;

    /**
     * 初始化
     * ServiceProvider constructor.
     * @param array $data
     * @param Container|null $container
     */
    public function __construct(array $data = array(), Container $container = null)
    {
        $this->setProperty($data);
        $this->setContainer($container ?: new Container());
        $this->register();
        $this->init();
    }

    /**
     * 设置默认服务提供器
     * @param ServiceProvider $provider
     */
    public static function setDefault(ServiceProvider $provider)
    {
        if (self::$default === null) {
            self::$default = $provider;
        }
    }

    /**
     * 获取默认服务提供器
     * @return ServiceProvider
     */
    public static function getDefault()
    {
        if (self::$default === null) {
            self::$default = new static();
        }
        return self::$default;
    }

    /**
     * 注册服务组件
     */
    public function register()
    {
    }

    /**
     * 初始化
     */
    public function init()
    {
    }

    /**
     * 获取容器
     * @return Container
     */
    public function container()
    {
        return $this->container;
    }

    /**
     * 设置容器
     * @param $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * 检测是否可提供服务组件
     * @param $name
     * @return bool
     */
    public function canService($name)
    {
        return array_key_exists($name, $this->services)
            || $this->container->has($name)
            || ocContainer()->has($name);
    }

    /**
     * 检测包含可提供服务组件
     * @param $name
     * @return bool
     */
    public function contain($name)
    {
        return array_key_exists($name, $this->services)
            || $this->container->has($name);
    }

    /**
     * 获取服务组件，如果没有就加载和新建
     * @param string $name
     * @param array $params
     * @param array $deps
     * @return array|mixed|object|void|null
     * @throws Exception
     */
    public function loadService($name, $params = array(), $deps = array())
    {
        $instance = $this->getService($name);

        if (empty($instance)) {
            if ($this->container && $this->container->hasBindAll($name)) {
                $instance = $this->container->get($name, $params, $deps);
                $this->setService($name, $instance);
            } elseif (ocContainer()->hasBindAll($name)) {
                $instance = ocContainer()->get($name, $params, $deps);
                $this->setService($name, $instance);
            }
        }

        return $instance;
    }

    /**
     * 新建动态服务组件
     * @param $name
     * @param array $params
     * @param array $deps
     * @return array|mixed|object|void|null
     * @throws Exception
     */
    public function createService($name, $params = array(), $deps = array())
    {
        if ($this->container && $this->container->has($name)) {
            return $this->container->create($name, $params, $deps);
        } elseif (ocContainer()->hasBindAll($name)) {
            return ocContainer()->create($name, $params, $deps);
        } else {
            ocService()->error->show('no_service', array($name));
        }
    }

    /**
     * 动态设置实例
     * @param $name
     * @param $service
     */
    public function setService($name, $service)
    {
        $this->services[$name] = $service;
    }

    /**
     * 检测服务组件是否存在
     * @param $name
     * @return bool
     */
    public function hasService($name)
    {
        return array_key_exists($name, $this->services);
    }

    /**
     * 获取已注册服务组件
     * @param $name
     * @return mixed|null
     */
    public function getService($name)
    {
        return array_key_exists($name, $this->services) ? $this->services[$name] : null;
    }

    /**
     * 获取资源服务
     * @param $name
     * @return array|mixed|object|void|null
     * @throws Exception
     */
    public function get($name)
    {
        return $this->loadService($name);
    }

    /**
     * 属性不存在时的处理
     * @param $key
     * @param $reason
     * @return array|mixed|object|void|null
     * @throws Exception
     */
    public function __none($key, $reason)
    {
        $instance = $this->loadService($key);
        if ($instance) {
            return $instance;
        }

        ocService('error', true)->show('no_service', array($key));
    }
}