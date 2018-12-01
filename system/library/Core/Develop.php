<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  开发者中心入口类Develop
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Base;

defined('OC_PATH') or exit('Forbidden!');

final class Develop extends Base
{
	public static $config;

	public static function run()
	{
		session_start();
		define('OC_DEV_DIR', OC_SYS . 'modules/develop/');
		$developConfig = ocImport(OC_DEV_DIR . 'config.php', true, false);
		self::$config = $developConfig;

		$controller = new DevelopController();
		$controller->run();

		die();
	}

    /**
     * 输出模板
     * @param $filename
     * @param $tpl
     * @param array $vars
     * @throws \Ocara\Exceptions\Exception
     */
	public static function tpl($filename, $tpl, array $vars = array())
	{
		(is_array($vars) && $vars) && extract($vars);
		
		if($tpl == 'global'){
			$path = OC_DEV_DIR.'global.php';
		} else {
			$path = OC_DEV_DIR . ($filename ? 'tpl/' . $filename : 'index') . '.php';
		}

		if (!ocFileExists($path)) {
			self::error($filename . '模板文件不存在.');
		}

		if($tpl == 'global'){
			$contentFile = $filename;
			include($path);
		} else {
			ocImport(OC_DEV_DIR . 'header.php');
			include($path);
			ocImport(OC_DEV_DIR . 'footer.php');
		}
		
		exit();
	}

    /**
     * 打印错误
     * @param $msg
     * @param string $tpl
     * @throws \Ocara\Exceptions\Exception
     */
	public static function error($msg, $tpl = 'module')
	{
		self::tpl('error', $tpl, get_defined_vars());
	}

    /**
     * 错误返回
     * @param $msg
     * @return string
     */
	public static function back($msg)
	{
		$back = ocService()->html->createElement('a', array(
			'href' => 'javascript:;',
			'onclick' => 'setTimeout(function(){history.back();},0)',
		), '返回');

		return  $msg . $back;
	}

	/**
	 * 检测登录
	 */
	public static function checkLogin()
	{
		return !empty($_SESSION['OC_DEV_LOGIN']);
	}
}