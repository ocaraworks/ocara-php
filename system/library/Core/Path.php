<?php
/**
 * 路径生成类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

defined('OC_PATH') or exit('Forbidden!');

class Path extends Basis
{
    protected $maps = array();
    protected $data = array();

    /**
     * 初始化
     */
    public function __construct()
    {
        $config = ocContainer()->config;
        $this->data = $config->get('APP_PATH_INFO', array());
    }

    /**
     * 路径映射
     * @param $dir
     * @param $path
     */
    public function setMap($dir, $path)
    {
        $this->maps[$dir] = $path;
    }

    /**
     * 生成文件或目录的路径
     * @param string $dir
     * @param string $path
     * @param string $root
     * @param bool $local
     * @param bool $isFile
     * @return bool|mixed|string
     */
    public function get($dir, $path, $root = null, $local = true, $isFile = true)
    {
        if (array_key_exists($dir, $this->maps)) {
            $rootPath = $this->maps[$dir];
        } else {
            $mapDir = $this->getConfigMapDir($dir, $local);
            $rootPath = ocDir($root, $mapDir);
        }

        $result = ocCommPath($rootPath . $path);
        if (isset($result)) {
            if ($local && $isFile && ($result = ocFileExists($result)) == false) {
                ocService()->error->show('not_exists_file', array($path));
            }
            $path = $result;
        }

        return $path;
    }

    /**
     * 获取映射路径
     * @param $dir
     * @return mixed|null
     */
    public function getMap($dir)
    {
        return array_key_exists($dir, $this->maps) ? $this->maps[$dir] : null;
    }

    /**
     * 获取配置映射路径
     * @param $dir
     * @param $local
     * @return string
     */
    public function getConfigMapDir($dir, $local)
    {
        $mapDir = $dir;

        if (isset($this->data['maps'][$dir])) {
            $mapDir = $this->data['maps'][$dir];
        }

        if ($local) {
            $belongs = $this->data['belongs'];
        } else {
            $belongs = $this->data['remote_belongs'];
        }

        if (isset($belongs[$mapDir])) {
            if (isset($this->data['replace'][$mapDir])) {
                $replace = $this->data['replace'][$mapDir];
            } else {
                $replace = $mapDir;
            }
            if ($dir == 'lang' && is_array($replace)) {
                $replace['lang'] = 'lang/' . ocService()->app->getLanguage();
            }
            $mapDir = $belongs[$mapDir] . OC_DIR_SEP . $replace;
        }

        return $mapDir;
    }
}