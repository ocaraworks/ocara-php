<?php
namespace Ocara\Sql\Databases;

class SqlFactory
{
    public static function create($databaseType)
    {
        $class = 'Ocara\Sql\Databases\\' . ucfirst($databaseType . 'Sql');
        $object = new $class();
        return $object;
    }
}