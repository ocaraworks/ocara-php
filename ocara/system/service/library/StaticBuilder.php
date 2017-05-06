<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   静态生成插件OCStatic
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Service;
use Ocara\Ocara;
use Ocara\ServiceBase;
use Ocara\StaticPath;
use Ocara\Call;

class StaticBuilder extends ServiceBase
{
	private $_dir;
	
	/**
	 * 析构函数
	 */
	public function __construct()
	{
		StaticPath::getInstance();

		if (empty(StaticPath::$open)) {
			$this->showError('not_exists_open_config');
		}

		$this->_dir = ocPath('static', false);
	}

	/**
	 * 全部静态生成
	 * @param array $callback
	 */
	public function genAll($callback)
	{
		$params = StaticPath::$params;

		if (empty($params)) return;

		foreach ($params as $module => $controller) {
			if (!is_array($controller)) continue;
			foreach ($controller as $action => $config) {
				if (is_array($config)) {
					foreach ($config as $key => $value) {
						$this->_genHtml($value, $module, $action, $key, $callback);
					}
				} elseif (is_string($config)) {
					$this->_genHtml($config, false, $module, $action, $callback);
				}
			}
		}
	}

	/**
	 * 按动作action生成HTML
	 * @param string|array $route
	 * @param array $data
	 */
	public function genAction($route, $data)
	{
		extract(Ocara::parseRoute($route));
		
		foreach ($data as $row) {
			list($file, $param) = StaticPath::getStaticFile($module, $controller, $action, $row);
			$url = ocUrl(array($module, $controller, $action), $param, false, false, false);
			$this->_createHtml($file, $url);
		}
	}
	
	/**
	 * 生成HTML
	 * @param arary  $params
	 * @param string $module
	 * @param string $controller
	 * @param string  $action
	 * @param string|array  $callback
	 */
	private function _genHtml($params, $module, $controller, $action, $callback)
	{
		$args = preg_match_all('/{(\w+)}/i', $params, $mt) ? $mt[1] : array();
		$args = array($module, $controller, $action, $args);

		$data    = $callback ? Call::run($callback, $args) : null;
		$pathMap = StaticPath::getMvcPathMap($module, $controller, $action);

		$this->_genRow($pathMap, $params, $module, $controller, $action, $data);
	}

	/**
	 * 生成一行
	 * @param string $mvcPathMap
	 * @param array  $params
	 * @param string $module
	 * @param string $controller
	 * @param string $action
	 * @param array  $data
	 */
	private function _genRow($mvcPathMap, $params, $module, $controller, $action, $data)
	{
		$route = array($module, $controller, $action);

		if ($data) {
			foreach ($data as $row) {
				if (is_array($row) && $row) {
					$paramsPathMap = StaticPath::getParamsPathMap(
						$params, $module, $controller, $action, $row
					);
					list($file, $param) = $paramsPathMap;
					if ($file = str_ireplace('{p}', $file, $mvcPathMap)) {
						$url = ocUrl($route, $param, false, false, false);
						$this->_createHtml($file, $url);
					}
				}
			}
		} else {
			$url  = ocUrl($route, array(), false, false, false);
			$file = trim(str_ireplace('{p}', OC_EMPTY, $mvcPathMap), OC_DIR_SEP);
			$file = $file. '.' . StaticPath::$fileType;
			$this->_createHtml($file, $url);
		}
	}

	/**
	 * 单页面生成
	 * @param string $file
	 * @param array  $url
	 */
	private function _createHtml($file, $url)
	{
		if ($file && $url) {
			$path = $file ? $this->_dir . $file : false;

			if ($content = ocRemote($url)) {
				return ocWrite($path, $content);
			}
		}

		return false;
	}
}
