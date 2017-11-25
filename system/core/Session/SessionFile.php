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
use \Exception;

defined('OC_PATH') or exit('Forbidden!');

final class SessionFile extends Base
{
	
    private $_savePath;
    
    /**
     * 析构函数
     */
    public function __construct()
    {
        $savePath = ocConfig('SESSION.location', 'sessions');
   	 	if (empty($savePath)) {
    		Error::show('invalid_save_path');
    	}
    	
    	$this->_savePath = ocPath('runtime', $savePath);
		if (!ocCheckPath($this->_savePath)) {
            if (!ocCheckPath($this->_savePath)) {
                Error::show('no_session_path');
            }
		}
    }
    
    /**
     * session打开
     * @param string $savePath
     * @param string $sessName
     */
    public function open($savePath, $sessName)
    {
		if (is_dir($this->_savePath)) {
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
    	$file = "{$this->_savePath}/sess_$id";

    	if (ocFileExists($file)) {
    		$time = filemtime($file);
    		$maxLifeTime  = @ini_get('session.gc_maxlifetime');
    		if ($time + $maxLifeTime >= time()) {
    			return stripslashes(ocRead($file, false));
    		}
    	}

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
            ocWrite("{$this->_savePath}/sess_$id", stripslashes($data));
        } catch(Exception $exception) {
            Error::exceptionHandler($exception);
        }

        return true;
    }

    /**
	 * 销毁session
	 * @param string $id
	 */
    public function destroy($id)
    {
        $file = "{$this->_savePath}/sess_{$id}";

        if (ocFileExists($file)) {
            @unlink($file);
        }

        return true;
    }

    /**
	 * Session垃圾回收
	 * @param integer $maxLifeTime
	 */
    public function gc($maxlifetime)
    {
        $files = glob("{$this->_savePath}/sess_*");
        foreach ($files as $file) {
            if (ocFileExists($file) && filemtime($file) + $maxlifetime < time()) {
                @unlink($file);
            }
        }

        return true;
    }
}