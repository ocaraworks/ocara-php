<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   缓存模型类Cache
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Model;

use Ocara\Cache as CacheFactory;

defined('OC_PATH') or exit('Forbidden!');

abstract class Cache extends ModelBase
{
    protected $_plugin;
    protected $_database;

    protected $_tag;
    protected $_master;
    protected $_slave;
    protected $_server;

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
        $this->_tag = self::getClass();
        $this->connect();

        if (method_exists($this, '_start')) $this->_start();
        if (method_exists($this, '_model')) $this->_model();

        return $this;
    }

    /**
     * 获取Model标记
     * @return string
     */
    public function getTag()
    {
        return $this->_tag;
    }

    /**
     * 获取当前服务器
     * @return mixed
     */
    public function getServer()
    {
        return $this->_server;
    }

    /**
     * 连接数据库
     * @param bool $master
     * @return null
     */
    public function connect($master = true)
    {
        $this->_plugin = null;

        if (!$master) {
            if (!is_object($this->_slave)) {
                $this->_slave = CacheFactory::create($this->_server, false, false);
            }
            $this->_plugin = $this->_slave;
        }

        if (!is_object($this->_plugin)) {
            if (!is_object($this->_master)) {
                $this->_master = CacheFactory::create($this->_server);
            }
            $this->_plugin = $this->_master;
        }

        if ($this->_database) {
            $this->_plugin->selectDatabase($this->_database);
        }

        $this->_plugin->setDataType($this->_dataType);

        return $this->_plugin;
    }
}
