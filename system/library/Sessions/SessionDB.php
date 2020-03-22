<?php
/**
 * Ocara开源框架 Session数据库处理类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

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

        $location = ocConfig(array('SESSION', 'options', 'location'));
        $this->container->bindSingleton('handler', $location);

        if (!is_object($this->handler)) {
            ocService()->error->show('failed_db_connect');
        }
    }

    /**
     * session打开
     * @return bool
     */
    public function open()
    {
        return is_object($this->handler);
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
        $handler = $this->handler;

        if (!is_object($handler)) return OC_EMPTY;

        $sessionData = $handler->read($id);
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

        return $this->handler->write($data);
    }

    /**
     * 销毁session
     * @param $id
     * @return bool
     */
    public function destroy($id)
    {
        return $this->handler->destory($id);
    }

    /**
     * Session垃圾回收
     * @param null $saveTime
     * @return bool
     */
    public function gc($saveTime = null)
    {
        return $this->handler->clear();
    }
}
