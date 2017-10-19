<?php
/**
 * Created by PhpStorm.
 * User: BORUI-DIY
 * Date: 2017/10/19 0019
 * Time: 上午 12:26
 */
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 缓存接口类Cache - 工厂类
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Event;

class RouteVisit extends Event
{

    /**
     * 当前权限检测
     * @param array $route
     */
    public static function authCheck(array $route)
    {
        $route = array_values($route);
        $callback = ocConfig('CALLBACK.auth.check', false);
        if ($callback) {
            self::printAccessResult(Call::run($callback, $route), $route);
        }
    }

    /**
     * 权限访问检测结果
     * @param bool $result
     * @param array $route
     */
    public static function printAccessResult($result, array $route)
    {
        $result = $result === true;
        if (!$result) {
            $callback = ocConfig('CALLBACK.auth.check', false);
            if ($callback) {
                Call::run($callback, $route);
            } else {
                Error::show('no_access');
            }
        }
    }

}