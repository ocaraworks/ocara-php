<?php
/**
 * 普通同步渲染视图类View
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Views;

use Ocara\Core\Response;
use Ocara\Core\ViewBase;
use Ocara\Exceptions\Exception;
use Ocara\Interfaces\View as ViewInterfaces;
use Ocara\Service\Interfaces\Template as TemplateInterface;

class Common extends ViewBase implements ViewInterfaces
{
    /**
     * @var $template 当前模板
     */
    protected $rootPath;

    private $route = array();
    private $tpl;
    private $fileType;
    private $template;
    private $vars;
    private $content;
    private $layout;
    private $parentLayout;

    private $useCache = true;
    private $useLayout = true;

    protected static $defaultStyle = 'defaults';

    /**
     * 初始化
     * Common constructor.
     * @param array $route
     * @throws Exception
     */
    public function __construct($route = array())
    {
        $this->fileType = ocConfig(array('TEMPLATE', 'file_type'), 'html');
        $this->template = ocConfig(array('TEMPLATE', 'default_style'), self::$defaultStyle, true);

        $this->route = $route;
        $this->loadEngine();
        $this->setLayout();
        return $this;
    }

    /**
     * 获取路由
     * @param null $name
     * @return array|mixed|null
     */
    public function getRoute($name = null)
    {
        if (func_get_args()) {
            return isset($this->route[$name]) ? $this->route[$name] : null;
        }

        return $this->route;
    }

    /**
     * 加载模板插件
     * @throws Exception
     */
    public function loadEngine()
    {
        if ($pluginClass = ocConfig(array('TEMPLATE', 'engine'), false)) {
            $path = $this->getViewPath(ocDir(array(
                'template',
                $this->route['module'],
                $this->route['controller']
            )));
            $plugin = new $pluginClass($path);
            $this->setPlugin($plugin);
        }
    }

    /**
     * 获取模板插件
     * @return null
     */
    public function engine()
    {
        $plugin = $this->plugin(false);
        if (is_object($plugin) && $plugin instanceof TemplateInterface) {
            return $plugin;
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
            return $this->useCache;
        }
        $this->useCache = $use ? true : false;
    }

    /**
     * 注册变量
     * @param string $name
     * @param mixed $value
     */
    public function assign($name, $value = null)
    {
        $variables = array();
        $plugin = $this->plugin(false);

        if (is_string($name)) {
            $variables[$name] = $value;
        } elseif (is_array($name)) {
            $variables = $name;
        }

        if ($plugin === null) {
            foreach ($variables as $name => $value) {
                $this->vars[$name] = $value;
            }
        } else {
            foreach ($variables as $name => $value) {
                $plugin->set($name, $value);
            }
        }
    }

    /**
     * 获取所有已注册变量
     * @param string $name
     * @param string $default
     * @return array|mixed|null
     */
    public function get($name = null, $default = null)
    {
        $plugin = $this->plugin(false);
        $vars = $plugin === null ? $this->vars : $plugin->get();

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

        return $vars ?: array();
    }

    /**
     * 是否含有模板变量
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this->vars);
    }

    /**
     * 设置layout
     * @param null $layout
     * @return $this
     * @throws Exception
     */
    public function setLayout($layout = null)
    {
        $layout = empty($layout) ? ocConfig(array('TEMPLATE', 'default_layout'), 'layout') : $layout;

        if ($layout) {
            $this->layout = $layout;
        }

        return $this;
    }

    /**
     * 继承Layout
     * @param $layout
     */
    public function inheritLayout($layout)
    {
        $this->parentLayout = $layout;
    }

    /**
     * 获取当前模块的视图路径
     * @param string $subPath
     * @param string $template
     * @return bool|mixed|string
     */
    public function getViewPath($subPath = null, $template = null)
    {
        $template = $template ?: $this->template;
        $module = $this->getRoute('module');

        if ($module) {
            $rootPath = $this->rootPath ?: null;
            $path = $this->getModuleViewPath($module, $subPath, $template, $rootPath);
        } else {
            $path = ocPath('view', $template . OC_DIR_SEP . $subPath);
            if (!ocFileExists($path)) {
                $path = ocPath('view', self::$defaultStyle . OC_DIR_SEP . $subPath);
            }
        }

        return $path;
    }

    /**
     * 获取其他模块视图路径
     * @param $module
     * @param null $subPath
     * @param null $template
     * @param null $rootPath
     * @return string
     */
    public function getModuleViewPath($module, $subPath = null, $template = null, $rootPath = null)
    {
        $template = $template ?: $this->template;

        if (empty($rootPath)) {
            if (OC_MODULE_PATH) {
                $rootPath = ocDir(array(OC_MODULE_PATH, $module, 'view'));
            } else {
                $rootPath = ocPath('modules', $module . '/view/');
            }
        }

        $path = $rootPath . $template . '/' . $subPath;
        if (!ocFileExists($path)) {
            $path = $rootPath . self::$defaultStyle . '/' . $subPath;
        }

        return $path;
    }

    /**
     * 设置当前模块模板路径
     * @param $rootPath
     */
    public function setModuleRootViewPath($rootPath)
    {
        $this->rootPath = $rootPath;
    }

    /**
     * 设置模板风格
     * @param $dir
     */
    public function setStyle($dir)
    {
        if (!is_dir($this->getViewPath($dir . '/template'))) {
            ocService()->error->show('not_exists_template', array($dir));
        }
        $this->template = $dir;
    }

    /**
     * 开启/关闭/检测是否使用layout
     * @param null|bool $use
     * @return bool
     */
    public function useLayout($use = null)
    {
        if ($use === null) {
            return $this->useLayout;
        }
        $this->useLayout = $use ? true : false;
    }

    /**
     * 获取layout
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * 显示部分
     * @param string $part
     * @param string $template
     * @param bool $show
     * @return string
     */
    public function readPart($part, $template = null, $show = true)
    {
        $part = ocForceArray($part);
        $html = array();

        foreach ($part as $value) {
            $path = $this->getViewPath('part/' . $value . '.php', $template);

            if (ocFileExists($path)) {
                if ($show) {
                    ($vars = $this->get()) && extract($vars);
                    include($path);
                } else {
                    $html[] = $this->readFile($path, true);
                }
            } else {
                ocService()->error->show('not_exists_part', array('file' => ltrim($value, OC_DIR_SEP)));
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
        $this->readPart($part, $template);
    }

    /**
     * 获取part内容
     * @param string $part
     * @param string $template
     * @return string
     */
    public function getPart($part, $template = null)
    {
        return $this->readPart($part, $template, false);
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
                return $this->wrapHtml($type, $url, $cache);
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
            $template = $this->template;
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
            echo $this->content;
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
        $this->tpl = $tpl;
    }

    /**
     * 获取当前模板
     */
    public function getTpl()
    {
        return $this->tpl;
    }

    /**
     * 渲染模板
     * @param null $file
     * @param array $vars
     * @param bool $required
     * @return mixed|null
     * @throws Exception
     */
    public function renderFile($file = null, array $vars = array(), $required = true)
    {
        $file = $file ?: $this->tpl;

        if ($vars && is_array($vars)) {
            $this->assign($vars);
        }

        if ($this->engine()) {
            $plugin = $this->plugin();
            $functions = ocConfig('DEFAULT_VIEW_ENGINE_FUNCTIONS');
            $functions = array_merge($functions, ocConfig('VIEW_ENGINE_FUNCTIONS', array()));
            foreach ($functions as $name) {
                $plugin->registerPlugin(array('function', $name, $name));
            }
            $plugin->set('View', $this);
        }

        $this->content = $this->readTpl($file, $required);
        if ($this->content) {
            if ($this->useLayout && $this->layout) {
                $this->renderLayout($this->layout);
            }
        }

        return $this->content;
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
            $this->content = $this->readFile($path);
        } else {
            ocService()->error->show('not_exists_layout', array($this->layout));
        }

        $parentLayout = $this->parentLayout;
        $this->parentLayout = null;

        if ($parentLayout) {
            $this->renderLayout($parentLayout);
        }
    }

    /**
     * 输出内容
     * @param $content
     * @throws Exception
     */
    public function outputFile($content)
    {
        $response = ocService()->response;

        if (ocConfig(array('FORM', 'data_cache'), 1)) {
            $response->setOption(
                'Cache-control',
                'private, must-revalidate'
            );
        }

        $response->setBody($content);
    }

    /**
     * 读取模板文件内容
     * @param string $file
     * @param bool $required
     * @return mixed|null
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

        $file = $file . '.' . $this->fileType;
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
            $this->plugin()->display($path);
        } else {
            ($vars = $this->get()) && extract($vars);
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
    private function wrapHtml($type, $value, $cache = true)
    {
        $cache = empty($cache) || ($cache && empty($this->useCache)) ? false : true;

        if (in_array($type, array('js', 'css'))) {
            if (!$cache) {
                $value = $value . "?time=" . date('YmdHis') . mt_rand(0, 1000) . '.' . $type;
            }
            if ($type == 'js') {
                $attr = array(
                    'src' => $value,
                    'type' => 'text/javascript',
                    'language' => 'javascript'
                );
                $value = ocService()->html->createElement('script', $attr, true);
            } else {
                $attr = array(
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

    /**
     * 输出内容
     * @param $content
     * @throws Exception
     */
    public function outputApi($content)
    {
        $contentType = ocService()->response->getHeaderOption('contentType');

        if (!$contentType) {
            $contentType = ocConfig('DEFAULT_PAGE_CONTENT_TYPE', 'html');
            ocService()->response->setContentType($contentType);
        }

        ocService()->response->setBody($content);
    }

    /**
     * 渲染API结果
     * @param $result
     * @return mixed
     * @throws Exception
     */
    public function renderApi($result)
    {
        $contentType = ocService()->response->getHeaderOption('contentType');

        if (!$contentType) {
            $contentType = ocConfig('DEFAULT_API_CONTENT_TYPE', 'json');
            ocService()->response->setContentType($contentType);
        }

        $content = ocService()->api->format($result, $contentType);
        return $content;
    }
}