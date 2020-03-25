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

    private static $connects = array();

    /**
     * 初始化方法
     * CacheBase constructor.
     * @param array $config
     * @param bool $required
     * @throws Exception
     */
    public function __construct(array $config, $required = true)
    {
        $connectName = $config['connect_name'];
        $this->setConnectName($connectName);

        if (!(isset(self::$connects[$connectName]) && self::$connects[$connectName] instanceof CacheBase)) {
            $this->connect($config, $required);
            self::$connects[$connectName] = $this->plugin();
        }

        $this->setPlugin(self::$connects[$connectName]);
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