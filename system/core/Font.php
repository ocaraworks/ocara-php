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
     * @param null|string $font
     * @return bool|mixed|string
     * @throws Exception\Exception
     */
    public function get($font)
    {
        if ($font) {
            if (!preg_match('/^.*\.\w{2,5}$/i', $font)) {
                $font = $font . '.ttf';
            }
        } else {
            $font = ocConfig('DEFAULT_FONT', 'simhei.ttf');
        }

        if (($path = ocFileExists(OC_SYS . 'data/fonts/' . $font, true)) or
            ($path = ocFileExists(OC_EXT . 'data/fonts/' . $font, true))
        ) return $path;

        Error::show('not_exists_font');
    }
}