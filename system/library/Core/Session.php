<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Session处理类Session
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Core;

use Ocara\Core\ServiceProvider;
use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class Session extends ServiceProvider
{
    /**
     * @var array $sessionProvidersMap
     */
    protected static $sessionProvidersMap = array(
        'file' => 'Ocara\Sessions\SessionFile',
        'database' => 'Ocara\Sessions\SessionDB',
        'cache' => 'Ocara\Sessions\SessionCache',
    );

    /**
     * 注册服务
     * @throws Exception
     */
    public function register()
    {
        parent::register(); // TODO: Change the autogenerated stub

        $class = ocConfig(array('SESSION', 'handler'), OC_EMPTY);
        $class = !empty(self::$sessionProvidersMap[$class]) ? self::$sessionProvidersMap[$class] : $class;
        $customizeSession = defined('OC_CUSTOMIZE_SESSION') ? OC_CUSTOMIZE_SESSION : true;

        if ($class && $customizeSession) {
            $this->container->bindSingleton('sessionHandler', function () use ($class) {
                $handler = new $class();
                return $handler;
            });
        }
    }

    /**
     * Session初始化处理
     * @param bool $start
     * @throws Exception
     */
    public function boot($start = true)
    {
        if ($this->canService('sessionHandler')) {
            $handler = $this->sessionHandler;
            session_set_save_handler(
                array(&$handler, 'open'),
                array(&$handler, 'close'),
                array(&$handler, 'read'),
                array(&$handler, 'write'),
                array(&$handler, 'destroy'),
                array(&$handler, 'gc')
            );
            register_shutdown_function('session_write_close');
        }

        $this->start($start);
    }

    /**
     * 启动Session
     * @param $start
     * @throws Exception
     */
    private function start($start)
    {
        $saveTime = intval(ocConfig(array('SESSION', 'options', 'save_time'), false));

        if ($saveTime) {
            $this->setSaveTime($saveTime);
        }

        if ($start && !isset($_SESSION)) {
            if (!headers_sent()) {
                session_start();
            }
        }

        if ($saveTime) {
            $this->setCookie($saveTime);
        }
    }

    /**
     * 获取session变量值
     * @param null $name
     * @return array|bool|mixed|null
     */
    public function get($name = null)
    {
        if (isset($name)) {
            return ocGet($name, $_SESSION);
        }

        return $_SESSION;
    }

    /**
     * 设置session变量
     * @param $name
     * @param $value
     */
    public function set($name, $value)
    {
        ocSet($_SESSION, $name, $value);
    }

    /**
     * 删除session变量
     * @param $name
     */
    public function delete($name)
    {
        ocDel($_SESSION, $name);
    }

    /**
     * 获取session ID
     */
    public function getId()
    {
        return session_id();
    }

    /**
     * 获取session Name
     */
    public function getName()
    {
        return session_name();
    }

    /**
     * 清空session数组
     * @param null $args
     */
    public function clear($args = null)
    {
        session_unset();
    }

    /**
     * > PHP7 回收session
     */
    public function gc()
    {
        session_gc();
    }

    /**
     * 检测session是否设置
     * @param $name
     * @return array|bool|mixed|null
     */
    public function has($name)
    {
        return ocKeyExists($name, $_SESSION);
    }

    /**
     * 释放session，删除session文件
     */
    public function destroy()
    {
        if (session_id()) {
            return session_destroy();
        }
    }

    /**
     * cookie保存session设置
     * @param string $saveTime
     * @param string $path
     * @param bool $domain
     * @param bool $secure
     * @param bool $httponly
     */
    public function setCookie($saveTime, $path = null, $domain = false, $secure = false, $httponly = true)
    {
        if (session_id()) {
            ocService()->cookie->set(
                session_name(),
                session_id(),
                $saveTime,
                $path,
                $domain,
                $secure,
                $httponly
            );
        }
    }

    /**
     * 设置session有效期(单位为秒)
     * @param integer $saveTime
     * @return string
     */
    public function setSaveTime($saveTime)
    {
        return @ini_set('session.gc_maxlifetime', $saveTime);
    }

    /**
     * 序列化session数组
     */
    public function serialize()
    {
        return session_encode();
    }

    /**
     * 反序列化session串
     * @param string $data
     * @return bool
     */
    public function unserialize($data)
    {
        return session_decode($data);
    }
}
