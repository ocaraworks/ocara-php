<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   应用视图类View
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;
use Ocara\Service\Interfaces\Template as TemplateInterface;

defined('OC_PATH') or exit('Forbidden!');

class View extends Base
{
	/**
	 * @var $_template 当前模板
	 */
	protected $_plugin = null;
	
	private $_tpl;
	private $_fileType;
	private $_template;
	private $_vars;
	private $_content;
	private $_layout;
	private $_parentLayout;

	private $_useCache  = true;
	private $_useLayout = true;
	
	/**
	 * 析构函数
	 */
	public function initialize()
	{
		$this->_fileType  = ocConfig('TEMPLATE.file_type', 'html');
		$this->_template = ocConfig('TEMPLATE.default', 'default');

		$this->loadEngine();
		$this->setLayout();
		return $this;
	}

	/**
	 * 加载模板插件
	 */
	public function loadEngine()
	{
		if ($pluginClass = ocConfig('TEMPLATE.engine', false)) {
			$route = $this->getRoute();
			$path  = ocDir(array(
				$this->_template,
				'template',
				$route['module'],
				$route['controller']
			));
			$this->_plugin = new $pluginClass($path);
		}
	}

	/**
	 * 获取模板插件
	 */
	public function engine()
	{
		if (is_object($this->_plugin) && $this->_plugin instanceof OCTemplateInterface) {
			return $this->_plugin;
		}
		return null;
	}

	/**
	 * 开启/关闭/检测是否使用CSS或JS缓存
	 * @param bool|null $use
	 */
	public function useCache($use = null)
	{
		if ($use === null) {
			return $this->_useCache;
		}
		$this->_useCache = $use ? true : false;
	}
	
	/**
	 * 注册变量
	 * @param string $name
	 * @param mixed $value
	 */
	public function assign($name, $value = null)
	{
		if (is_string($name)) {
			$vars[$name] = $value;
		} elseif (is_array($name)) {
			$vars = $name;
		}

		$null = $this->_plugin === null;
		foreach ($vars as $name => $value) {
			if ($null) {
				$this->_vars[$name] = $value;
				ocGlobal('View', $this);
			} else {
				$this->_plugin->setVar($name, $value);
			}
		}
	}

	/**
	 * 获取所有已注册变量
	 * @param string $name
	 * @param mixed $default
	 */
	public function getVar($name = null, $default = null)
	{
		$vars = $this->_plugin === null ? $this->_vars : $this->_plugin->getVar();
		
		if ($name) {
			$vars = (array)$vars;
			if (array_key_exists($name, $vars)) {
				return $vars[$name];
			}
			if (func_num_args() >= 2) {
				return $default;
			}
			Error::show('not_exists_template_var', array($name));
		}
		
		return $vars ? $vars : array();
	}

	/**
	 * 是否含有模板变量
	 * @param string $name
	 */
	public function hasVar($name)
	{
		return array_key_exists($name, $this->_vars);
	}
	
	/**
	 * 设置layout
	 * @param string $layout
	 */
	public function setLayout($layout = false)
	{
		$layout = empty($layout) ? ocConfig('TEMPLATE.default_layout', 'layout') : $layout;
		
		if ($layout) {
			$this->_layout = $layout;
		}

		return $this;
	}

	/**
	 * 继承Layout
	 * @param $layout
	 */
	public function inheritLayout($layout)
	{
		$this->_parentLayout = $layout;
	}

	/**
	 * 设置模板风格
	 * @param $dir
	 */
	public function setTemplate($dir)
	{
		if (!is_dir(ocPath('view', 'template/' . $dir))) {
			Error::show('not_exists_template', array($dir));
		}
		$this->_template = $dir;
	}

	/**
	 * 开启/关闭/检测是否使用layout
	 * @param bool|null $use
	 */
	public function useLayout($use = null)
	{
		if ($use === null) {
			return $this->_useLayout;
		}
		$this->_useLayout = $use ? true : false;
	}

	/**
	 * 获取layout
	 */
	public function getLayout()
	{
		return $this->_layout;
	}

	/**
	 * 显示部分
	 * @param string $part
	 * @param string $template
	 * @param bool $show
	 */
	public function _readPart($part, $template = false, $show = true)
	{
		$template = $template ? $template : $this->_template;
		$part     = ocForceArray($part);
		$html     = array();

		foreach ($part as $value) {
			$path = ocPath('view', ocDir($template, 'part') . $value . '.php');

			if (ocFileExists($path)) {
				if ($show) {
					($vars = $this->getVar()) && extract($vars);
					include ($path);
				} else {
					$html[] = $this->readFile($path, true);
				}
			} else {
				Error::show('not_exists_part', array('file' => ltrim($value,OC_DIR_SEP)));
			}
		}
		
		if (empty($show) && $html) {
			return implode(OC_EMPTY, $html);
		}
	}

	/**
	 * 显示part内容
	 * @param string $part
	 * @param string $template
	 */
	public function showPart($part, $template = false)
	{
		$this->_readPart($part, $template);
	}

	/**
	 * 获取part内容
	 * @param string $part
	 * @param string $template
	 */
	public function getPart($part, $template = false)
	{
		return $this->_readPart($part, $template, false);
	}

	/**
	 * 加载JS或CSS文件
	 * @param string $path
	 * @param string $template
	 * @param bool $cache
	 */
	public function load($path, $template = false, $cache = true)
	{
		if ($path) {
			$path = (array)$path;
			foreach ($path as $val) {
				echo $this->wrap($val, $template, $cache);
			}
		}
	}
	
	/**
	 * 获取图片URL
	 * @param string $path
	 * @param string $template
	 */
	public function getImageUrl($path, $template = false)
	{
		return $this->getUrl('images', $path, $template);
	}

	/**
	 * 获取包装的HTML
	 * @param string $path
	 * @param string $template
	 * @param bool $cache
	 */
	public function wrap($path, $template = false, $cache = true)
	{
		$array = explode('.', $path);
		
		if (count($array) >= 2) {
			$type = end($array);
			if (in_array(strtolower(trim($type)), array('css', 'js'))) {
				$url = $this->getUrl($type, $path, $template);
				return $this->_wrapHtml($type, $url, $cache);
			}
		}
		
		return false;
	}

	/**
	 * 获取URL
	 * @param string $type
	 * @param string $path
	 * @param string $template
	 */
	public function getUrl($type, $path, $template = false)
	{
		if (empty($template) && $type != 'js') {
			$template = $this->_template;
		}
		
		$path = $type == 'js' ? $path : $template . OC_DIR_SEP . $path;
		return ocRealUrl($type, $path);
	}

	/**
	 * 显示其他模板文件
	 * @param string $file
	 */
	public function showTpl($file = false)
	{
		if (empty($file)) {
			echo $this->_content;
		} else {
			$file = (array)$file;
			foreach ($file as $row) {
				$content = $this->readTpl($row);
				if ($content) echo $content;
			}
		}
	}

	/**
	* 设置当前模板
	* @param string $tpl
	*/
	public function setTpl($tpl = false)
	{
		$this->_tpl = $tpl;
	}
	
	/**
	* 获取当前模板
	*/
	public function getTpl()
	{
		return $this->_tpl;
	}
	
	/**
	 * 渲染模板
	 * @param string $file
	 * @param array $vars
	 * @param bool $required
	 */
	public function render($file = false, array $vars = array(), $required = true)
	{
		$file = $file ? $file : $this->_tpl;

		if ($vars && is_array($vars)) {
			$this->assign($vars);
		}

		if ($this->engine()) {
			$func = $this->_getRequiredFunctions();
			foreach ($func as $name) {
				$this->_plugin->registerPlugin(array('function', $name, $name));
			}
			$this->_plugin->setVar('View', $this);
		}

		$this->_content = $this->readTpl($file, $required);
		if ($this->_content) {
			if ($this->_useLayout && $this->_layout) {
				$this->renderLayout($this->_layout);
			}
		}

		return $this->_content;
	}

	/**
	 * 渲染Layout
	 * @param $layout
	 * @throws Exception
	 */
	public function renderLayout($layout)
	{
		$path = ocPath('view', ocDir($this->_template, 'layout') . $layout . '.php');
		if (ocFileExists($path)) {
			$this->_content = $this->readFile($path);
		} else {
			Error::show('not_exists_layout', array($this->_layout));
		}

		$parentLayout = $this->_parentLayout;
		$this->_parentLayout = null;

		if ($parentLayout) {
			$this->renderLayout($parentLayout);
		}
	}

	/**
	 * 读取模板文件内容
	 * @param string $file
	 * @param bool $required
	 */
	public function readTpl($file, $required = true)
	{
		if (empty($file)) return null;
		
		$path = ocDir($this->_template, 'template');
		$rootTmpl = false;
		
		if (preg_match('/^\/(.*)$/', $file, $mt)) {
			$file = $mt[1];
			$rootTmpl = true;
		} else {
			$route = $this->getRoute();
			$path = $path . ocDir($route['module'], $route['controller']);
		}
		
		$file = $file . '.' . $this->_fileType;
		$realPath = ocPath('view', $path . $file);

		if (ocFileExists($realPath) == false) {
			if ($required) {
				Error::show('not_exists_template_file', array($file));
			} else {
				return null;
			}
		}
		
		if ($this->engine() && empty($rootTmpl)) $realPath = $file;
		return $this->readFile($realPath, false);
	}

	/**
	 * 缓存HTML
	 * $data为文件路径或内容
	 * @param string $path
	 * @param bool $ban
	 */
	public function readFile($path, $ban = true)
	{
		ob_start();
		
		if ($this->engine() && empty($ban)) {
			$this->_plugin->display($path);
		} else {
			($vars = $this->getVar()) && extract($vars);
			include ($path);
		}

		$content = ob_get_contents();
		ob_end_clean();
		return Filter::bom($content);
	}

	/**
	 * 加载助手
	 * @param string $path
	 * @param array $params
	 * @param string $template
	 */
	public function helper($path, array $params = array(), $template = false)
	{
		$template = $template ? $template : $this->_template;
		$file 	  = ocPath('view', $template . '/helper/' . $path . '.php');
		$helper	  = explode(OC_DIR_SEP, $path);
		$helper   = array_pop($helper);
		
		if ($file && ocFileExists($file)) {
			include_once($file);
			if (class_exists($helper, false)) {
				return ocClass($helper, $params);
			} elseif (function_exists($helper)) {
				return call_user_func_array('Ocara\View\Helper' . OC_NS_SEP . $helper, $params);
			}
		} 
		
		Error::show('not_exists_helper', array($helper));
	}
	
	/**
	 * 获取要注册的函数名称
	 */
	private function _getRequiredFunctions()
	{
		return array(
			'ocGlobal', 	'ocPath', 	'ocFile', 		'ocRealUrl', 
			'ocSimpleUrl', 	'ocUrl', 	'ocConfig',  	'ocGet', 
			'ocSet', 		'ocDel',	'ocKeyExists',	'ocFileExists', 
			'ocPrint', 	'ocDump',
		);
	}
	
	/**
	 * 包装为HTML内容
	 * @param string $type
	 * @param string $value
	 * @param bool $cache
	 */
	private function _wrapHtml($type, $value, $cache = true)
	{
		$cache = empty($cache) || ($cache && empty($this->_useCache)) ? false : true;
		
		if (in_array($type, array('js', 'css'))) {
			if (!$cache) {
				$value = $value . "?time=" . date('YmdHis') . mt_rand(0, 1000) . '.' . $type;
			}
			if ($type == 'js') {
				$attr  = array(
					'src' => $value,
					'type' => 'text/javascript',
					'language' => 'javascript'
				);
				$value = Html::createElement('script', $attr, true);
			} else {
				$attr  = array(
					'href' => $value,
					'type' => 'text/css',
					'rel' => 'stylesheet'
				);
				$value = Html::createElement('link', $attr, false);
			}
			return $value . OC_ENTER;
		}
			
		return $value;
	}
}