<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  字体处理类Font
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

class Font extends Base
{
    /**
     * 获取字体路径
     * @param null|string $name
     * @return bool|mixed|string
     * @throws Exception\Exception
     */
    public function get($name)
    {
        if ($name) {
            if (!preg_match('/^.*\.\w{2,5}$/i', $name)) {
                $name = $name . '.ttf';
            }
        } else {
            $name = ocConfig('DEFAULT_FONT', 'simhei.ttf');
        }

        if (($path = ocFileExists(OC_SYS . 'data/fonts/' . $name, true)) or
            ($path = ocFileExists(OC_EXT . 'data/fonts/' . $name, true))
        ) return $path;

        Error::show('not_exists_font');
    }
}