<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 路径生成类Path
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

use Ocara\Basis;

defined('OC_PATH') or exit('Forbidden!');

class Path extends Basis
{
	/**
	 * 初始化
	 */
	public function __construct()
	{
	    $config = Container::getDefault()->config;
		$paths = $config->get('APP_PATH_INFO', array());
		$this->setProperty($paths);
	}

    /**
     * 生成文件或目录的路径
     * @param string $dir
     * @param string $path
     * @param string $root
     * @param bool $local
     * @param bool $isFile
     * @return bool|mixed|string
     * @throws Exception\Exception
     */
    public function get($dir, $path, $root = null, $local = true, $isFile = true)
    {
        $mapDir = $dir;

        if (isset($this->_properties['map'][$dir])) {
            $mapDir = $this->_properties['map'][$dir];
        }

        if (isset($this->_properties['belong'][$mapDir])) {
            if (isset($this->_properties['replace'][$mapDir])) {
                $replace = $this->_properties['replace'][$mapDir];
            } else {
                $replace = $mapDir;
            }
            if ($dir == 'lang') {
                $replace['lang'] = 'lang/' . OC_LANGUAGE;
            }
            $mapDir = $this->_properties['belong'][$mapDir] . OC_DIR_SEP . $replace;
        }

        $result = ocDir($root, $mapDir) . $path;
        if (isset($result)) {
            if ($local && $isFile && ($result = ocFileExists($result)) == false) {
                Ocara::services()->error->show('not_exists_file', array($path));
            }
            $path = $result;
        }

        return $path;
	}
}