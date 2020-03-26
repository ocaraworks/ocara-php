<?php

namespace Ocara\Service;

use Ocara\Core\Base;
use Ocara\Core\ServiceBase;
use Ocara\Exceptions\Exception;

class BaseValidate extends ServiceBase
{

    /**
     * 验证
     * @param string $result
     * @param string $error
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function validate($result, $error, array $params = array())
    {
        if (!$result) {
            return array($error, self::getMessage($error, $params));
        }
        return array();
    }
}