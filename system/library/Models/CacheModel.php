<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   缓存模型类Cache
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Models;

use Ocara\Exceptions\Exception;
use Ocara\Core\CacheFactory;
use Ocara\Core\ModelBase;

defined('OC_PATH') or exit('Forbidden!');

abstract class CacheModel extends ModelBase
{
    protected $database;

    protected $tag;
    protected $prefix;
    protected $master;
    protected $slave;
    protected $connectName;

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
     * @param $name
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
        return $this->connectName;
    }

    /**
     * 连接缓存
     * @return mixed
     * @throws Exception
     */
    public function connect()
    {
        $plugin = CacheFactory::getInstance($this->connectName);
        $this->setPlugin($plugin);

        if (!ocEmpty($this->database)) {
            $plugin->selectDatabase($this->database);
        }

        return $plugin;
    }
}
