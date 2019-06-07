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
        parent::register(); // TODO: Change the autogenerated stub

        $location = ocConfig(array('SESSION', 'options', 'location'), '\Ocara\Service\Models\Session', true);
        $this->_container->bindSingleton('_plugin', $location);

        if (!(is_object($this->plugin) && $this->plugin instanceof ModelBase)) {
            ocService()->error->show('failed_db_connect');
        }
    }

	/**
	 * session打开
	 */
	public function open()
	{
		return is_object($this->plugin) && $this->plugin instanceof ModelBase;
	}

	/**
	 * session关闭
	 */
	public function close()
	{
		$this->plugin = null;
		return true;
	}

	/**
	 * 读取session信息
	 * @param string $id
	 */
	public function read($id)
	{
		$sessionData = $this->plugin->read($id);
		return $sessionData ? stripslashes($sessionData) : OC_EMPTY;
	}

	/**
	 * 保存session
	 * @param string $id
	 * @param string $data
	 */
	public function write($id, $data)
	{
		$datetimeFormat = ocConfig(array('DATE_FORMAT', 'datetime'));
		$maxLifeTime = @ini_get('session.gc_maxlifetime');
		$now = date($datetimeFormat);
		$expires = date($datetimeFormat, strtotime("{$now} + {$maxLifeTime} second"));

		$data = array(
			'session_id' 	  	  => $id,
			'session_expire_time' => $expires,
			'session_data' 	  	  => stripslashes($data)
		);

		$this->plugin->write($data);
		$result = $this->plugin->errorExists();

		return $result === true;
	}

	/**
	 * 销毁session
	 * @param string $id
	 */
	public function destroy($id)
	{
		$this->plugin->destory($id);
		$result = $this->plugin->errorExists();

		return $result === true;
	}

	/**
	 * Session垃圾回收
	 * @param integer $saveTime
	 */
	public function gc($saveTime = null)
	{
		$this->plugin->clear();
		$result = $this->plugin->errorExists();

		return $result === true;
	}
}
