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
    protected $_plugin;
    protected $_database;

    protected $_tag;
    protected $_prefix;
    protected $_master;
    protected $_slave;
    protected $_connectName;

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
        return $this->_tag;
    }

    /**
     * 获取当前服务器
     * @return mixed
     */
    public function getConnectName()
    {
        return $this->_connectName;
    }

    /**
     * 连接缓存
     * @param bool $master
     * @return mixed|null
     * @throws Exception
     */
    public function connect($master = true)
    {
        $this->_plugin = null;

        if (!$master) {
            if (!is_object($this->_slave)) {
                $this->_slave = CacheFactory::create($this->_connectName, false, false);
            }
            $this->_plugin = $this->_slave;
        }

        if (!is_object($this->_plugin)) {
            if (!is_object($this->_master)) {
                $this->_master = CacheFactory::create($this->_connectName);
            }
            $this->_plugin = $this->_master;
        }

        if ($this->_database) {
            $this->_plugin->selectDatabase($this->_database);
        }

        return $this->_plugin;
    }
}
