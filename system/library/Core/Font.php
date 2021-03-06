<?php
/**
 * 字体处理类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use Ocara\Exceptions\Exception;

class Font extends Base
{
    /**
     * 获取字体
     * @param string $name
     * @param mixed $args
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