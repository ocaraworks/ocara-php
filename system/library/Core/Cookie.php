<?php
/**
 * Cookie处理类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use Ocara\Exceptions\Exception;

class Cookie extends Base
{
    /**
     * 获取cookie变量值
     * @param string|array $key
     * @return array|bool|mixed|null
     */
    public function get($key = null)
    {
        if (isset($key)) {
            return ocGet($key, $_COOKIE);
        }

        return $_COOKIE;
    }

    /**
     * 删除cookie变量
     * @param $key
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @throws Exception
     */
    public function delete($key, $path = '', $domain = '', $secure = false, $httponly = true)
    {
        $this->set($key, null, time() - 1, $path, $domain, $secure, $httponly);
    }

    /**
     * 检测cookie是否设置
     * @param string|array $key
     * @return array|bool|mixed|null
     */
    public function has($key)
    {
        return ocKeyExists($key, $_COOKIE);
    }

    /**
     * 新建cookie变量
     * @param string $name
     * @param string $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httponly
     * @throws Exception
     */
    public function set($name, $value, $expire = 0, $path = '', $domain = '', $secure = false, $httponly = true)
    {
        $expire = intval($expire);
        $expire = $expire ? time() + $expire : 0;
        $path = $path ?: ocConfig(array('COOKIE', 'path'), OC_EMPTY);
        $domain = $domain ?: ocConfig(array('COOKIE', 'domain'), OC_EMPTY);
        $secure = $secure ? true : ocConfig(array('COOKIE', 'secure'), false);
        $httponly = $httponly ? true : ocConfig(array('COOKIE', 'httponly'), true);

        $secure = $secure ? true : false;
        $httponly = $httponly ? true : false;

        setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }
}
