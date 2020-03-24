<?php
/**
 * 资源中间件类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use Ocara\Exceptions\Exception;

class ResourceManager extends ServiceProvider
{
    /**
     * @throws Exception
     */
    public function init()
    {
        parent::init();
        $resources = ocConfig('RESOURCE');
        foreach ($resources as $name => $resourceList) {
            $resourceList = (array)$resourceList;
            array_walk($resourceList, array($this, 'bindResource'), $name);
        }
    }

    /**
     * 绑定资源
     * @param $value
     * @param $key
     * @param $name
     * @throws Exception
     */
    public function bindResource($value, $key, $name)
    {
        if ($value) {
            $this->container->bind($name . '.' . $key, $value);
        }
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
}