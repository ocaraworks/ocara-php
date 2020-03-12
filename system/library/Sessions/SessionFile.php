<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Session文件方式处理类SessionFile
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Sessions;

use Ocara\Exceptions\Exception;
use Ocara\Core\ServiceProvider;

defined('OC_PATH') or exit('Forbidden!');

class SessionFile extends ServiceProvider
{
    private $savePath;

    /**
     * 初始化
     * @throws Exception
     */
    public function init()
    {
        $savePath = ocConfig(array('SESSION', 'options', 'location'), null);

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

        $this->savePath = $savePath;
    }

    /**
     * session打开
     * @param $savePath
     * @param $sessName
     * @return bool
     */
    public function open($savePath, $sessName)
    {
        return is_dir($this->savePath);
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
     * @return bool|string
     */
    public function read($id)
    {
        $file = "{$this->savePath}/sess_$id";

        if (ocFileExists($file)) {
            $time = filemtime($file);
            $maxLifeTime = @ini_get('session.gc_maxlifetime');
            if ($time + $maxLifeTime >= time()) {
                return stripslashes(ocRead($file, false));
            }
        }

        return OC_EMPTY;
    }

    /**
     * 保存session
     * @param $id
     * @param $data
     * @return bool
     */
    public function write($id, $data)
    {
        try {
            ocWrite("{$this->savePath}/sess_$id", stripslashes($data));
        } catch (\Exception $exception) {
            ocService()->error->show($exception->getMessage());
        }

        return true;
    }

    /**
     * 销毁session
     * @param $id
     * @return bool
     */
    public function destroy($id)
    {
        $file = "{$this->savePath}/sess_{$id}";

        if (ocFileExists($file)) {
            @unlink($file);
        }

        return true;
    }

    /**
     * Session垃圾回收
     * @param $maxLifeTime
     * @return bool
     */
    public function gc($maxLifeTime)
    {
        $files = glob("{$this->savePath}/sess_*");
        foreach ($files as $file) {
            if (ocFileExists($file) && filemtime($file) + $maxLifeTime < time()) {
                @unlink($file);
            }
        }

        return true;
    }
}