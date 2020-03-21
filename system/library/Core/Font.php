<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  字体处理类Font
 * @Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Core;

use Ocara\Exceptions\Exception;

class Font extends Base
{
    /**
     * 获取字体
     * @param null $name
     * @param null $args
     * @return bool|mixed|string
     * @throws Exception
     */
    public function get($name = null, $args = null)
    {
        if (!$name) {
            $name = ocConfig('DEFAULT_FONT', 'Verdana');
        }

        if (!preg_match('/^.*\.\w{2,5}$/i', $name)) {
            $name = $name . '.ttf';
        }

        $path = ocFileExists(OC_SYS . 'data/fonts/' . $name);

        if (!$path) {
            $path = ocFileExists(OC_EXT . 'data/fonts/' . $name);
        }

        if (!$path) {
            $path = ocPath('data', 'fonts/' . $name);
        }

        if ($path) return $path;

        ocService()->error->show('not_exists_font');
    }
}