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
use Ocara\Error;
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
        $prefix = ocConfig('SESSION.location', 'sessions');
        $this->_prefix = $prefix . '_';
        $this->_plugin = Cache::getInstance($cacheName);
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
     */
    public function read($id)
    {
    	$this->_plugin->getVar($this->_prefix . $id);
    	return false;
    }

    /**
	 * 保存session
	 * @param string $id
	 * @param string $data
	 */
    public function write($id, $data)
    {
        try {
            $this->_plugin->setVar($this->_prefix . $id, $data);
        } catch(Exception $e) {
            Error::exceptionHandler($e);
        }

        return true;
    }

    /**
	 * 销毁session
	 * @param string $id
	 */
    public function destroy($id)
    {
        $this->_plugin->deleteVar($this->_prefix . $id);
        return true;
    }

    /**
	 * Session垃圾回收
	 * @param integer $maxLifeTime
	 */
    public function gc($maxlifetime)
    {
        return true;
    }
}