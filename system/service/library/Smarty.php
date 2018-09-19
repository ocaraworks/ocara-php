<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  smarty模板调用插件Smarty
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Service;

use Ocara\Ocara;
use Ocara\ServiceBase;
use Ocara\Service\Interfaces\Template as TemplateInterface;

defined('OC_PATH') or exit('Forbidden!');

class Smarty extends ServiceBase implements TemplateInterface
{
	protected $_plugin = null;

	/**
	 * 析构函数
	 * @param string $path
	 */
	public function __construct($path)
	{
		ocImport(OC_SYS . 'modules/smarty/Smarty.class.php');

		if (!class_exists('smarty', false)) {
            Ocara::services()->error->show('no_the_special_class', array('smarty'));
		}

		$this->_plugin = new \Smarty();
		$templateDir  = ocPath('view', $path);
		$compileDir   = ocPath('runtime', 'smarty/cmp/');
		$cacheDir     = ocPath('runtime', 'smarty/cache/');

		ocCheckPath($templateDir, 0755, true);
		ocCheckPath($compileDir, 0755, true);
		ocCheckPath($cacheDir, 0755, true);

		$this->_plugin->setTemplateDir($templateDir);
		$this->_plugin->setCompileDir($compileDir);
		$this->_plugin->setCacheDir($cacheDir);

		if (ocConfig('SMARTY.use_cache', false)) {
			$this->_plugin->cache_lifetime = 60;
			$this->_plugin->caching = true;
		} else {
			$this->_plugin->caching = false;
		}
	
		$this->_plugin->left_delimiter = ocConfig('SMARTY.left_sign');
		$this->_plugin->right_delimiter = ocConfig('SMARTY.right_sign');
	}

	/**
	 * @see Interface_OCTemplate::assign()
	 */
	public function setVar($name, $value)
	{
		$this->_plugin->assign($name, $value);
	}

	/**
	 * @see Interface_OCTemplate::getVars()
	 */
	public function getVar($name = null)
	{
		return $this->_plugin->getTemplateVars($name);
	}

	/**
	 * @see Interface_OCTemplate::registerObject()
	 */
	public function registerObject($params)
	{
		call_user_func_array(array(&$this->_plugin, 'registerObject'), $params);
	}

	/**
	 * @see Interface_OCTemplate::registerPlugin()
	 */
	public function registerPlugin($params)
	{
		call_user_func_array(array(&$this->_plugin, 'registerPlugin'), $params);
	}
}
