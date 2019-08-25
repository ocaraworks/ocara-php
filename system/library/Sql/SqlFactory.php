<?php
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
    public static function create($databaseType, DatabaseBase $database)
    {
        $class = 'Ocara\Sql\Databases\\' . ucfirst($databaseType . 'Sql');
        $object = new $class($database);
        return $object;
    }
}