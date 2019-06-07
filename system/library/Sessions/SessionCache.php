<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Session文件方式处理类SessionFile
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Sessions;

use Ocara\Core\Base;
use Ocara\Core\CacheFactory;
use Ocara\Exceptions\Exception;
use Ocara\Core\ServiceProvider;

defined('OC_PATH') or exit('Forbidden!');

class SessionCache extends ServiceProvider
{
	
    private $_plugin;
    private $_prefix;

    /**
     * 注册服务
     * @throws Exception
     */
    public function register()
    {
        parent::register(); // TODO: Change the autogenerated stub

        $cacheName = ocConfig(array('SESSION', 'options', 'server'), CacheFactory::getDefaultServer());
        $this->_container->bindSingleton('_plugin', function () use ($cacheName){
            CacheFactory::create($cacheName);
        });
    }

    /**
     * 初始化
     */
    public function init()
    {
        $prefix = ocConfig(array('SESSION', 'options', 'location'), 'session');
        $this->_prefix = $prefix;
    }
    
    /**
     * session打开
     */
    public function open()
    {
		if (is_object($this->plugin)) {
			return true;
		}
        return false;
    }

    /**
     * session关闭
     */
    public function close()
    {
        return true;
    }

    /**
     * 读取session信息
     * @param string $id
     * @return bool
     */
    public function read($id)
    {
    	$this->plugin->get($this->_prefix . $id);
    	return false;
    }

    /**
     * 保存session
     * @param string $id
     * @param string $data
     * @return bool
     */
    public function write($id, $data)
    {
        try {
            $this->plugin->set($this->_prefix . $id, $data);
        } catch(Exception $exception) {
            ocService()->error->show($exception->getMessage());
        }

        return true;
    }

    /**
     * 销毁session
     * @param string $id
     * @return bool
     */
    public function destroy($id)
    {
        $this->plugin->delete($this->_prefix . $id);
        return true;
    }

    /**
     * Session垃圾回收
     * @param integer $maxlifetime
     * @return bool
     */
    public function gc($maxlifetime)
    {
        return true;
    }
}