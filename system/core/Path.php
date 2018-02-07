<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 路径生成类Path
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

class Path extends Base
{
	/**
	 * 初始化
	 */
	public function __construct()
	{
		$paths = ocConfig('APP_PATH_INFO', array(), true);
		$paths['replace']['lang'] = 'lang/' . Ocara::language();
		$this->set($paths);
	}

    /**
     * 生成文件或目录的路径
     * @param string $name
     * @param string $path
     * @param string $root
     * @param bool $local
     * @param bool $isFile
     * @return array|mixed
     */
    public function &get($name = null, $args = null)
    {
        if (func_num_args()) {
            $args = func_get_args();
            $path = array_key_exists(1, $args) ? $args[1] : OC_EMPTY;
            $root = array_key_exists(2, $args) ? $args[2] : OC_EMPTY;
            $local = array_key_exists(3, $args) ? (bool)$args[3] : true;
            $isFile = array_key_exists(4, $args) ? (bool)$args[4] : true;
            $mapDir = $name;

            if (isset($this->_properties['map'][$name])) {
                $mapDir = $this->_properties['map'][$name];
            }

            if (isset($this->_properties['belong'][$mapDir])) {
                if (isset($this->_properties['replace'][$mapDir])) {
                    $replace = $this->_properties['replace'][$mapDir];
                } else {
                    $replace = $mapDir;
                }
                $mapDir = $this->_properties['belong'][$mapDir] . OC_DIR_SEP . $replace;
            }

            $result = ocDir($root, $mapDir) . $path;
            if (isset($result)) {
                if ($local && $isFile && ($result = ocFileExists($result)) == false) {
                    Error::show('not_exists_file', array($path));
                }
                $path = $result;
            }

            return $path;
        }

        return $this->_properties;
	}
}