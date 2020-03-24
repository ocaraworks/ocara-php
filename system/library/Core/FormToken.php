<?php
/**
 * 表单令牌处理类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use Ocara\Exceptions\Exception;

class FormToken extends Base
{
    /**
     * 生成表单令牌
     * @param $formName
     * @param $route
     * @return mixed|string
     * @throws Exception
     */
    public function generate($formName, $route)
    {
        $token = null;
        $routeStr = implode(OC_EMPTY, $route);

        if (ocService()->resources->contain('form.generate_token')) {
            $token = ocService()
                ->resources
                ->get('form.generate_token')
                ->handle($formName, $route);
        }

        if (empty($token)) {
            $token = md5($routeStr . $formName . md5(ocService()->code->getRandNumber(5)) . uniqid(mt_rand()));
        }

        return $token;
    }
}