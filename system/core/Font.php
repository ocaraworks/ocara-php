<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  字体处理类Font
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

use Ocara\Base;

class Font extends Base
{
    /**
     * 获取字体
     * @param string $name
     * @param null $args
     * @return bool|mixed|string
     */
    public function get($name = null, $args = null)
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

        Ocara::services()->error->show('not_exists_font');
    }
}