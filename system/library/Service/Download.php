<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   文件PHP即时下载插件Download
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Service;

use Ocara\Core\ServiceBase;
use Ocara\Exceptions\Exception;

class Download extends ServiceBase
{
    /**
     * 下载文件
     * @param string $filePath
     * @param string $saveName
     * @param string $encode
     * @return bool
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
	
		if(!$saveName){
			$options = ocService()->filter->path($filePath);
			$saveName = end($options);	
		}

		$content = ocRead($filePath);
		$this->downloadContent($saveName, $content, $encode);
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

        $saveName = $saveName ? : ocBasename($filePath);
        $saveType = $mt[1];
        $mineTypes = ocConfig('MINE_TYPES');

        if (!array_key_exists($saveType, $mineTypes)) {
            $this->showError('invalid_download_type', array($saveType));
        }

        $mine = $mineTypes[$saveType];
        
        ocService()->response->setHeader(array(
            "Content-Type:{$mine};encoding={$encode};name={$saveName}",
            "Content-Disposition: attachment; filename={$saveName}",
            'Pragma: no-cache',
            'Cache-Control: no-cache, must-revalidate',
            'Expires: 0',
        ));

        ocService()->response->setBody($content);
    }
}
