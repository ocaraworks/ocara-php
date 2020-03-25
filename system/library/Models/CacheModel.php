<?php
/**
 * 缓存模型基类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Models;

use ReflectionException;
use Ocara\Exceptions\Exception;
use Ocara\Core\ModelBase;

abstract class CacheModel extends ModelBase
{
    protected $database;

    protected $tag;
    protected $prefix;
    protected $master;
    protected $slave;
    protected $serverName;

    /**
     * Model constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * 初始化
     */
    public function init()
    {
        $this->tag = self::getClass();
        $this->connect();

        if (method_exists($this, '__start')) $this->__start();
        if (method_exists($this, '__model')) $this->__model();

        return $this;
    }

    /**
     * 获取Model标记
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * 获取缓存KEY
     * @param string $name
     * @param string $separator
     * @return string
     */
    public function getKeyName($name = '', $separator = '')
    {
        if (is_array($name)) {
            $name = implode($separator, $name);
        }
        return $this->prefix . $name;
    }

    /**
     * 获取当前服务器
     * @return mixed
     */
    public function getConnectName()
    {
        return $this->serverName;
    }

    /**
     * 连接缓存
     * @return object|null
     * @throws Exception
     * @throws ReflectionException
     */
    public function connect()
    {
        $plugin = ocService()->caches->make($this->serverName);
        $this->setPlugin($plugin);

        if (!ocEmpty($this->database)) {
            $plugin->selectDatabase($this->database);
        }

        return $plugin;
    }
}
