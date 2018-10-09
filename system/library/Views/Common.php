<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   普通视图类View
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Views;

use Ocara\Core\ViewBase;
use Ocara\Interfaces\View as ViewInterfaces;
use Ocara\Service\Interfaces\Template as TemplateInterface;

defined('OC_PATH') or exit('Forbidden!');

class Common extends ViewBase implements ViewInterfaces
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
	 * 初始化
	 */
	public function __construct()
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
			$route = ocService()->app->getRoute();
			$path  = $this->getViewPath(ocDir(array(
				'template',
				$route['module'],
				$route['controller']
			)));
			$this->_plugin = new $pluginClass($path);
		}
	}

	/**
	 * 获取模板插件
	 */
	public function engine()
	{
		if (is_object($this->_plugin) && $this->_plugin instanceof TemplateInterface) {
			return $this->_plugin;
		}
		return null;
	}

	/**
	 * 检测是否使用CSS或JS缓存
	 * @param bool|null $use
	 * @return bool
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
	 * @return array|null
	 * @throws \Ocara\Exceptions\Exception
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
			ocService()->error->show('not_exists_template_var', array($name));
		}
		
		return $vars ? : array();
	}

	/**
	 * 是否含有模板变量
	 * @param string $name
	 * @return bool
	 */
	public function hasVar($name)
	{
		return array_key_exists($name, $this->_vars);
	}

	/**
	 * 设置layout
	 * @param string $layout
	 * @return $this
	 */
	public function setLayout($layout = null)
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
     * 获取模块视图路径
     * @param $subPath
     */
	public function getViewPath($subPath = null, $template = null)
    {
        $template = $template ? : $this->_template;
        $path = ocService()->app->getRoute('module')
            . '/view/'
            . $template
            . OC_DIR_SEP
            . $subPath;
	    return ocPath('modules', $path);
    }

	/**
	 * 设置模板风格
	 * @param $dir
	 */
	public function setTemplate($dir)
	{
		if (!is_dir($this->getViewPath('template/' . $dir))) {
			ocService()->error->show('not_exists_template', array($dir));
		}
		$this->_template = $dir;
	}

	/**
	 * 开启/关闭/检测是否使用layout
	 * @param null|bool $use
	 * @return bool
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
	public function _readPart($part, $template = null, $show = true)
	{
		$part = ocForceArray($part);
		$html = array();

		foreach ($part as $value) {
			$path = $this->getViewPath('part/' . $value . '.php', $template);

			if (ocFileExists($path)) {
				if ($show) {
					($vars = $this->getVar()) && extract($vars);
					include ($path);
				} else {
					$html[] = $this->readFile($path, true);
				}
			} else {
				ocService()->error->show('not_exists_part', array('file' => ltrim($value,OC_DIR_SEP)));
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
	public function showPart($part, $template = null)
	{
		$this->_readPart($part, $template);
	}

	/**
	 * 获取part内容
	 * @param string $part
	 * @param string $template
	 */
	public function getPart($part, $template = null)
	{
		return $this->_readPart($part, $template, false);
	}

	/**
	 * 加载JS或CSS文件
	 * @param string $path
	 * @param string $template
	 * @param bool $cache
	 */
	public function load($path, $template = null, $cache = true)
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
	public function getImageUrl($path, $template = null)
	{
		return $this->getUrl('images', $path, $template);
	}

	/**
	 * 获取包装的HTML
	 * @param string $path
	 * @param string $template
	 * @param bool $cache
	 */
	public function wrap($path, $template = null, $cache = true)
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
	public function getUrl($type, $path, $template = null)
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
	public function showTpl($file = null)
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
	public function setTpl($tpl = null)
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
	public function render($file = null, array $vars = array(), $required = true)
	{
		$file = $file ? : $this->_tpl;

		if ($vars && is_array($vars)) {
			$this->assign($vars);
		}

		if ($this->engine()) {
			$functions = ocConfig('DEFAULT_VIEW_ENGINE_FUNCTIONS');
            $functions = array_merge($functions, ocConfig('VIEW_ENGINE_FUNCTIONS', array()));
			foreach ($functions as $name) {
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
	 * 输出内容
	 * @param string $data
	 */
	public function output($data)
	{
		if (ocConfig('FORM.data_cahce', 1)) {
			ocService()->response->setOption(
				'Cache-control',
				'private, must-revalidate'
			);
		}

		ocService()->response->sendHeaders();
		echo $data['content'];
	}

	/**
	 * Ajax输出
	 * @param mixed $data
	 * @param array $message
	 */
	public function ajaxOutput($data, $message)
	{
        ocService()->ajax->show('success', $message, $data);
	}

	/**
	 * 渲染Layout
	 * @param $layout
	 * @throws Exception
	 */
	public function renderLayout($layout)
	{
		$path = $this->getViewPath('layout/' . $layout . '.php');
		if (ocFileExists($path)) {
			$this->_content = $this->readFile($path);
		} else {
			ocService()->error->show('not_exists_layout', array($this->_layout));
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
		
		$path = 'template/';
		$rootTmpl = false;
		
		if (preg_match('/^\/(.*)$/', $file, $mt)) {
			$file = $mt[1];
			$rootTmpl = true;
		} else {
			$route = ocService()->app->getRoute();
			$path = $path . ocDir($route['module'], $route['controller']);
		}
		
		$file = $file . '.' . $this->_fileType;
		$realPath = $this->getViewPath($path . $file);

		if (ocFileExists($realPath) == false) {
			if ($required) {
				ocService()->error->show('not_exists_template_file', array($file));
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

		return ocService()->filter->bom($content);
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
				$value = ocService()->html->createElement('script', $attr, true);
			} else {
				$attr  = array(
					'href' => $value,
					'type' => 'text/css',
					'rel' => 'stylesheet'
				);
				$value = ocService()->html->createElement('link', $attr, false);
			}
			return $value . PHP_EOL;
		}
			
		return $value;
	}
}