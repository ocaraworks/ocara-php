<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   控制器特性基类FeatureBase
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Feature;
use Ocara\Base;
use Ocara\Container;
use Ocara\Validator;
use Ocara\Service\Pager;

defined('OC_PATH') or exit('Forbidden!');

class FeatureBase extends Base
{
    /**
     * 获取验证器
     */
    public function getValidator(Container $container)
    {
        ocImport(OC_LIB . 'Validator.php');
        $class = ocConfig('VALIDATE_CLASS', 'Ocara\Service\Validate', true);
        $validator = new Validator(new $class);
        return $validator;
    }

    /**
     * 获取分页器
     */
    public function getPager(Container $container)
    {
        ocImport(OC_SERVICE . 'library/Pager.php');
        $pager = new Pager();
        return $pager;
    }
}