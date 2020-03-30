<?php
/**
 * 表单令牌处理类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use \ReflectionException;
use Ocara\Exceptions\Exception;

class FormToken extends Base
{
    const EVENT_GENERATE_TOKEN = 'generateToken';

    /**
     * @throws Exception
     */
    public function registerEvents()
    {
        $this->event(self::EVENT_GENERATE_TOKEN)
            ->resource()
            ->append(ocConfig('RESOURCE.form.generateToken', null));
    }

    /**
     * 生成表单令牌
     * @param string $formName
     * @param array $route
     * @return array|mixed|string
     * @throws Exception
     */
    public function generate($formName, array $route)
    {
        $routeStr = implode(OC_EMPTY, $route);
        $token = $this->fire(self::EVENT_GENERATE_TOKEN, array($formName, $route));

        if (empty($token)) {
            $token = md5($routeStr . $formName . md5(ocService()->code->getRandNumber(5)) . uniqid(mt_rand()));
        }

        return $token;
    }
}