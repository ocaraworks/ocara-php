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
use Ocara\Exceptions\Exception;
use Ocara\Core\ServiceProvider;

defined('OC_PATH') or exit('Forbidden!');

class SessionFile extends ServiceProvider
{
	
    private $_savePath;

    /**
     * 启动
     * @throws Exception
     */
    public function init()
    {
        $savePath = ocConfig('SESSION.options.location', null);

   	 	if ($savePath) {
            $savePath = ocPath('runtime', $savePath);
            if (!ocCheckPath($savePath)) {
                if (!ocCheckPath($savePath)) {
                    ocService()->error->show('no_session_path');
                }
            }
    	} else {
            $savePath = session_save_path();
        }

        if (!is_writable($savePath)) {
            ocService()->error->show('not_write_session_path');
        }

        $this->_savePath = $savePath;
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
            ocService()->error->show($exception->getMessage());
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