<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   视图基类View
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Base;
use Ocara\Service\Interfaces\Template as TemplateInterface;

defined('OC_PATH') or exit('Forbidden!');

class ViewBase extends Base
{
    /**
     * @var $_template 当前模板
     */
    protected $_rootPath;

    private $_tpl;
    private $_fileType;
    private $_template;
    private $_vars;
    private $_content;
    private $_layout;
    private $_parentLayout;

    private $_useCache  = true;
    private $_useLayout = true;

    protected static $_defaultTemplate = 'defaults';

    /**
     * 初始化
     */
    public function __construct()
    {
        $this->_fileType  = ocConfig('TEMPLATE.file_type', 'html');
        $this->_template = ocConfig('TEMPLATE.default', self::$_defaultTemplate, true);

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
        $variables = array();
        if (is_string($name)) {
            $variables[$name] = $value;
        } elseif (is_array($name)) {
            $variables = $name;
        }

        $null = $this->_plugin === null;
        foreach ($variables as $name => $value) {
            if ($null) {
                $this->_vars[$name] = $value;
                ocGlobal('View', $this);
            } else {
                $this->_plugin->set($name, $value);
            }
        }
    }

    /**
     * 获取所有已注册变量
     * @param string $name
     * @param string $default
     * @return array|mixed|null
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
     * @throws \Ocara\Exceptions\Exception
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
     * 获取路由
     * @param null $name
     * @return array|mixed|null
     */
    public function getRoute($name = null)
    {
        $route = $this->getVar('route');

        if (func_get_args()) {
            return isset($route[$name]) ? $route[$name] : null;
        }

        return $route;
    }

    /**
     * 获取当前模块的视图路径
     * @param string $subPath
     * @param string $template
     * @return bool|mixed|string
     * @throws \Ocara\Exceptions\Exception
     */
    public function getViewPath($subPath = null, $template = null)
    {
        $template = $template ? : $this->_template;
        $module = $this->getRoute('module');

        if ($module) {
            $rootPath = $this->_rootPath ? : null;
            $path = $this->getModuleViewPath($module, $subPath, $template, $rootPath);
        } else {
            $path = ocPath('view', $template . OC_DIR_SEP . $subPath);
            if (!ocFileExists($path)) {
                $path = ocPath('view', self::$_defaultTemplate . OC_DIR_SEP . $subPath);
            }
        }

        return $path;
    }

    /**
     * 获取其他模块视图路径
     * @param $module
     * @param string $subPath
     * @param string $template
     * @param string $rootPath
     * @return mixed
     * @throws \Ocara\Exceptions\Exception
     */
    public function getModuleViewPath($module, $subPath = null, $template = null, $rootPath = null)
    {
        $template = $template ? : $this->_template;

        if (empty($rootPath)) {
            if (OC_MODULE_PATH) {
                $rootPath = ocDir(array(OC_MODULE_PATH, $module, 'view'));
            } else {
                $rootPath = ocPath('modules', $module .'/view/');
            }
        }

        $path = $rootPath . $template . '/' . $subPath;
        if (!ocFileExists($path)) {
            $path = $rootPath . self::$_defaultTemplate . '/' . $subPath;
        }

        return $path;
    }

    /**
     * 设置当前模块模板路径
     * @param $rootPath
     */
    public function setModuleRootViewPath($rootPath)
    {
        $this->_rootPath = $rootPath;
    }

    /**
     * 设置模板风格
     * @param $dir
     * @throws \Ocara\Exceptions\Exception
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
     * @return string
     * @throws \Ocara\Exceptions\Exception
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
     * @throws \Ocara\Exceptions\Exception
     */
    public function showPart($part, $template = null)
    {
        $this->_readPart($part, $template);
    }

    /**
     * 获取part内容
     * @param string $part
     * @param string $template
     * @return string
     * @throws \Ocara\Exceptions\Exception
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
     * @return string
     */
    public function getImageUrl($path, $template = null)
    {
        return $this->getUrl('images', $path, $template);
    }

    /**
     * 获取包装的HTML
     * @param $path
     * @param null $template
     * @param bool $cache
     * @return bool|string
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
     * @return string
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
     * @throws \Ocara\Exceptions\Exception
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
     * @param null $file
     * @param array $vars
     * @param bool $required
     * @return mixed|null
     * @throws \Ocara\Exceptions\Exception
     */
    public function renderFile($file = null, array $vars = array(), $required = true)
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
            $this->_plugin->set('View', $this);
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
     * @throws \Ocara\Exceptions\Exception
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
     * 输出内容
     * @param $content
     * @throws \Ocara\Exceptions\Exception
     */
    public function outputFile($content)
    {
        $response = ocService()->response;

        if (ocConfig('FORM.data_cache', 1)) {
            $response->setOption(
                'Cache-control',
                'private, must-revalidate'
            );
        }

        $response->setBody($content);
    }

    /**
     * 输出内容
     * @param array $data
     */
    public function outputApi($data)
    {
        ocService()->response->setContentType($data['contentType']);
        $response->setBody($content);
    }

    /**
     * 渲染结果
     * @param |null $result
     * @return mixed|void|null
     * @throws \Ocara\Exceptions\Exception
     */
    public function renderApi($result)
    {
        $response = ocService()->response;

        if ($callback = ocConfig('SOURCE.ajax.return_result', null)) {
            $result = call_user_func_array($callback, array($result));
        }

        $statusCode = $response->getOption('statusCode');
        if (!$statusCode && !ocConfig('API.is_send_error_code', 0)) {
            $response->setStatusCode(Response::STATUS_OK);
            $result['statusCode'] = $response->getOption('statusCode');
        }

        $contentType = $response->getOption('contentType');
        switch ($contentType)
        {
            case 'json':
                $content = json_encode($result);
                break;
            case 'xml':
                $content = $this->getXmlResult($result);
                break;
        }

        $response->setBody($content);
    }

    /**
     * 读取模板文件内容
     * @param string $file
     * @param bool $required
     * @return mixed|null
     * @throws \Ocara\Exceptions\Exception
     */
    public function readTpl($file, $required = true)
    {
        if (empty($file)) return null;

        if ($this->engine()) {
            $realPath = $file;
            return $this->readFile($realPath, false);
        }

        if (preg_match('/^\/(.*)$/', $file, $mt)) {
            $file = $mt[1];
            $filePath = explode('|', $file);
            $module = array_shift($filePath);
            $file = implode('/', $filePath);
            $path = $this->getModuleViewPath($module, 'template');
        } else {
            $route = ocService()->app->getRoute();
            $path = $this->getViewPath('template/' . $route['controller']);
        }

        $file = $file . '.' . $this->_fileType;
        $realPath = ocDir($path) . $file;

        if (ocFileExists($realPath) == false) {
            if ($required) {
                ocService()->error->show('not_exists_template_file', array($file));
            } else {
                return null;
            }
        }

        return $this->readFile($realPath, false);
    }

    /**
     * 缓存HTML
     * @param string $path
     * @param bool $ban
     * @return mixed
     */
    public function readFile($path, $ban = true)
    {
        ob_start();

        if ($this->engine() && empty($ban)) {
            $this->_plugin->display($path);
        } else {
            ($vars = $this->getVar()) && extract($vars);
            include($path);
        }

        $content = ob_get_contents();
        ob_end_clean();

        return ocService()->filter->bom($content);
    }

    /**
     * 包装为HTML内容
     * @param array|string $type
     * @param string $value
     * @param bool $cache
     * @return string
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