<?php
/**
 * 文件下载插件类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Service;

use Ocara\Core\ServiceBase;
use Ocara\Exceptions\Exception;

class Download extends ServiceBase
{
    /**
     * 下载文件
     * @param $filePath
     * @param null $saveName
     * @param string $encode
     * @throws Exception
     */
    public function download($filePath, $saveName = null, $encode = 'utf-8')
    {
        $expression = '/^.+\.(\w{2,4})$/';

        if (!preg_match($expression, $filePath)) {
            $this->showError('fault_path');
        }

        if (!ocFileExists($filePath)) {
            $this->showError('not_exists_file');
        }

        if (!$saveName) {
            $saveName = basename($filePath);
        }

        $content = ocRead($filePath);
        $this->downloadContent($content, $saveName, $encode);
    }

    /**
     * 下载内容
     * @param $content
     * @param $saveName
     * @param string $encode
     * @return bool
     * @throws Exception
     */
    public function downloadContent($content, $saveName, $encode = 'utf-8')
    {
        if (!$content) return false;

        $expression = '/^.+\.(\w{2,4})$/';

        if (!preg_match($expression, $saveName, $mt)) {
            $this->showError('failed_save_filename');
        }

        $saveType = $mt[1];
        $mineTypes = ocConfig('MINE_TYPES');

        if (!array_key_exists($saveType, $mineTypes)) {
            $this->showError('invalid_download_type', array($saveType));
        }

        $mine = $mineTypes[$saveType];
        $headers = array(
            "Content-Type:{$mine};encoding={$encode};name={$saveName}",
            "Content-Disposition: attachment; filename={$saveName}",
            'Pragma: no-cache',
            'Cache-Control: no-cache, must-revalidate',
            'Expires: 0',
        );

        ocService()->response->setHeader($headers);
        ocService()->response->setBody($content);
    }

    /**
     * 显示文件内容
     * @param $fileName
     * @param string $encode
     * @throws Exception
     */
    public function showFile($fileName, $encode = 'utf-8')
    {
        $content = ocRead($fileName);
        return $this->showContent($content, $fileName, $encode);
    }

    /**
     * 显示内容
     * @param $content
     * @param $fileOrType
     * @param string $encode
     * @throws Exception
     */
    public function showContent($content, $fileOrType, $encode = 'utf-8')
    {
        $expression = '/^.+\.(\w{2,4})$/';
        $mineTypes = ocConfig('MINE_TYPES');

        if (preg_match($expression, $fileOrType, $mt)) {
            $type = $mt[1];
        } else {
            $type = $fileOrType;
        }

        if (!isset($mineTypes[$type])) {
            ocService()->error->show('not_exists_mime_config', $type);
        }

        $mine = $mineTypes[$type];

        $headers = array(
            "Content-Type:{$mine};",
            'Pragma: no-cache',
            'Cache-Control: no-cache, must-revalidate',
            'Expires: 0',
        );

        ocService()->response->setHeader($headers);
        ocService()->response->setBody($content);
    }
}
