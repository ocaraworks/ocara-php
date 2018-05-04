<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Session文件方式处理类SessionFile
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Session;
use Ocara\Base;
use Ocara\Ocara;
use Ocara\Cache;
use \Exception;

defined('OC_PATH') or exit('Forbidden!');

final class SessionCache extends Base
{
	
    private $_plugin;
    private $_prefix;
    
    /**
     * 析构函数
     */
    public function __construct()
    {
        $cacheName = ocConfig('SESSION.server', 'default');
        $prefix = ocConfig('SESSION.location', 'session');
        $this->_prefix = $prefix . '_';
        $this->_plugin = Cache::create($cacheName);
    }
    
    /**
     * session打开
     */
    public function open()
    {
		if (is_object($this->_plugin)) {
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
    	$this->_plugin->get($this->_prefix . $id);
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
            $this->_plugin->set($this->_prefix . $id, $data);
        } catch(Exception $exception) {
            Ocara::services()->error->show($exception->getMessage());
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
        $this->_plugin->delete($this->_prefix . $id);
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