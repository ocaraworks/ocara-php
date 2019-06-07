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
		$mineTypes = ocConfig('MINE_TYPES');

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

		if (!preg_match($expression, $saveName, $mt)) {
			$this->showError('failed_save_filename');
		}
		
		$saveName = $saveName ? : ocBasename($filePath);
		$saveType = $mt[1];

		if (!array_key_exists($saveType, $mineTypes)) {
			$this->showError('invalid_download_type', array($saveType));
		}
		
		$mine = $mineTypes[$saveType];
		$content = ocRead($filePath);

		if (!$content) return false;
		
		header("Content-Type:{$mine};encoding={$encode};name={$saveName}");
		header("Content-Disposition: attachment; filename={$saveName}");
		header('Pragma: no-cache');
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: 0');
		
		echo ($content);
	}
}
