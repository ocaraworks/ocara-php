<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Session数据库方式处理类SessionDB
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Sessions;

use Ocara\Core\ModelBase;
use Ocara\Exceptions\Exception;
use Ocara\Core\ServiceProvider;

defined('OC_PATH') or exit('Forbidden!');

class SessionDB extends ServiceProvider
{
    /**
     * 注册服务
     * @throws Exception
     */
    public function register()
    {
        parent::register();

        $location = ocConfig(array('SESSION', 'options', 'location'), '\Ocara\Service\Models\Session', true);
        $this->container->bindSingleton('plugin', $location);

        $plugin = $this->plugin;
        if (!(is_object($plugin) && $plugin instanceof ModelBase)) {
            ocService()->error->show('failed_db_connect');
        }
    }

    /**
     * session打开
     * @return bool
     */
    public function open()
    {
        $plugin = $this->plugin;
        return is_object($plugin) && $plugin instanceof ModelBase;
    }

    /**
     * session关闭
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * 读取session信息
     * @param $id
     * @return string
     */
    public function read($id)
    {
        $plugin = $this->plugin;

        if (!is_object($plugin)) return OC_EMPTY;

        $sessionData = $plugin->read($id);
        $result = $sessionData ? stripslashes($sessionData) : OC_EMPTY;

        return $result;
    }

    /**
     * 保存session
     * @param $id
     * @param $data
     * @return bool
     * @throws Exception
     */
    public function write($id, $data)
    {
        $datetimeFormat = ocConfig(array('DATE_FORMAT', 'datetime'));
        $maxLifeTime = @ini_get('session.gc_maxlifetime');
        $now = date($datetimeFormat);
        $expires = date($datetimeFormat, strtotime("{$now} + {$maxLifeTime} second"));

        $data = array(
            'session_id' => $id,
            'session_expire_time' => $expires,
            'session_data' => stripslashes($data)
        );

        $plugin = $this->plugin;
        $plugin->write($data);
        $result = $plugin->errorExists();

        return $result === true;
    }

    /**
     * 销毁session
     * @param $id
     * @return bool
     */
    public function destroy($id)
    {
        $plugin = $this->plugin;
        $plugin->destory($id);
        $result = $plugin->errorExists();

        return $result === true;
    }

    /**
     * Session垃圾回收
     * @param null $saveTime
     * @return bool
     */
    public function gc($saveTime = null)
    {
        $plugin = $this->plugin;
        $plugin->clear();
        $result = $plugin->errorExists();

        return $result === true;
    }
}
