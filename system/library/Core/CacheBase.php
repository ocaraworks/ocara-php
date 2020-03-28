<?php
/**
 * 缓存基类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use Ocara\Exceptions\Exception;

class CacheBase extends Base
{
    protected $connectName;
    protected $config = array();

    /**
     * 初始化方法
     * CacheBase constructor.
     * @param array $config
     * @param bool $required
     */
    public function __construct(array $config, $required = true)
    {
        $this->setConnectName($config['connect_name']);
        $this->connect($config, $required);
    }

    /**
     * 连接数据库实例
     * @param $config
     * @param bool $required
     */
    public function connect($config, $required = true)
    {
    }

    /**
     * 设置连接名称
     * @param string $connectName
     */
    public function setConnectName($connectName)
    {
        $this->connectName = $connectName;
    }

    /**
     * 获取连接名称
     * @return string|null
     */
    public function getConnectName()
    {
        return $this->connectName;
    }
}