<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 路径生成类Path
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Basis;

defined('OC_PATH') or exit('Forbidden!');

class Path extends Basis
{
    protected $_maps = array();
    protected $_data = array();

	/**
	 * 初始化
	 */
	public function __construct()
	{
	    $config = ocContainer()->config;
		$this->_data = $config->get('APP_PATH_INFO', array());
	}

    /**
     * 路径映射
     * @param $dir
     * @param $path
     */
	public function setMap($dir, $path)
    {
	    $this->_maps[$dir] = $path;
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
        if (array_key_exists($dir, $this->_maps)) {
            $rootPath = $this->_maps[$dir];
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
        return array_key_exists($dir, $this->_maps) ? $this->_maps[$dir] : null;
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

        if (isset($this->_data['maps'][$dir])) {
            $mapDir = $this->_data['maps'][$dir];
        }

        if ($local) {
            $belongs = $this->_data['belongs'];
        } else {
            $belongs = $this->_data['remote_belongs'];
        }

        if (isset($belongs[$mapDir])) {
            if (isset($this->_data['replace'][$mapDir])) {
                $replace = $this->_data['replace'][$mapDir];
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