<?php
/**
 * Sql生成器工厂类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Sql;

use Ocara\Core\DatabaseBase;

class SqlFactory
{
    /**
     * 生成SQL实例
     * @param $databaseType
     * @param DatabaseBase $database
     * @return mixed
     */
    public function create($databaseType, DatabaseBase $database)
    {
        $class = 'Ocara\Sql\Databases\\' . ucfirst($databaseType . 'Sql');
        $object = new $class($database);
        return $object;
    }
}