<?php
/**
 * 服务提供器类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use Ocara\Interfaces\ServiceProvider as ServiceProviderInterface;
use Ocara\Exceptions\Exception;
use Ocara\Service\Validate as ValidateService;
use \Ocara\Service\Auth as AuthService;
use \Ocara\Service\Xml as XmlService;
use \Ocara\Service\FileCache as FileCacheService;
use \Ocara\Service\VerifyCode as VerifyCodeService;
use \Ocara\Service\Mail as MailService;
use \Ocara\Service\Ftp as FtpService;
use \Ocara\Service\Image as ImageService;
use \Ocara\Service\FileLog as FileLogService;
use \Ocara\Service\Upload as UploadService;
use \Ocara\Service\File as FileService;
use \Ocara\Service\Code as CodeService;
use \Ocara\Service\Excel as ExcelService;
use \Ocara\Service\Date as DateService;
use \Ocara\Service\Download as DownloadService;
use \Ocara\Service\StaticBuilder as StaticBuilderService;
use \Ocara\Service\Pager as PagerService;
use \Ocara\Service\ErrorOutput as ErrorOutputService;
use \Ocara\Dispatchers\Common as CommonDispatcher;
use \Ocara\Views\Common as CommonView;
use \Ocara\Views\Api as ApiView;

/**
 * Class ServiceProvider
 * @property Config $config
 * @property Path $path
 * @property Loader $loader
 * @property Container $container
 * @property CacheFactory $caches
 * @property DatabaseFactory $databases
 * @property ExceptionHandler $exceptionHandler
 * @property Request $request
 * @property Response $response
 * @property Api $api
 * @property Error $error
 * @property Filter $filter
 * @property Url $url
 * @property Lang $lang
 * @property Cookie $cookie
 * @property Session $session
 * @property Route $route
 * @property Transaction $transaction
 * @property Font $font
 * @property StaticPath $staticPath
 * @property Globals $globals
 * @property FormToken $formToken
 * @property Validator $validator
 * @property Event $event
 * @property Log $log
 * @property Form $form
 * @property Html $html
 * @property FormManager $formManager
 * @property ValidateService $validate
 * @property AuthService $auth
 * @property XmlService $xml
 * @property FileCacheService $fileCache
 * @property VerifyCodeService $verifyCode
 * @property MailService $mail
 * @property FtpService $ftp
 * @property ImageService $image
 * @property FileLogService $fileLog
 * @property UploadService $upload
 * @property FileService $file
 * @property CodeService $code
 * @property ExcelService $excel
 * @property DateService $date
 * @property DownloadService $download
 * @property StaticBuilderService $staticBuilder
 * @property PagerService $pager
 * @property ErrorOutputService $errorOutput
 * @property CommonDispatcher $dispatcher
 * @property CommonView|ApiView $view
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
     * @param string $name
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
     * @param string $name
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
     * @param string $name
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
     * @param string $name
     * @param object|string $service
     */
    public function setService($name, $service)
    {
        $this->services[$name] = $service;
    }

    /**
     * 检测服务组件是否存在
     * @param string $name
     * @return bool
     */
    public function hasService($name)
    {
        return array_key_exists($name, $this->services);
    }

    /**
     * 获取已注册服务组件
     * @param string $name
     * @return mixed|null
     */
    public function getService($name)
    {
        return array_key_exists($name, $this->services) ? $this->services[$name] : null;
    }

    /**
     * 获取资源服务
     * @param string $name
     * @return array|mixed|object|void|null
     * @throws Exception
     */
    public function get($name)
    {
        return $this->loadService($name);
    }

    /**
     * 属性不存在时的处理
     * @param string $key
     * @param string $reason
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