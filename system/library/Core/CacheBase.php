<?php
/**
 
 * Ocara开源框架 缓存客户端接口基类CacheBase
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class CacheBase extends Base
{
    protected $connectName;

    protected $slaves = array();
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
     * @param $connectName
     */
    public function setConnectName($connectName)
    {
        $this->connectName = $connectName;
    }

    /**
     * 获取连接名称
     * @return mixed
     */
    public function getConnectName()
    {
        return $this->connectName;
    }
}