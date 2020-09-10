<?php

namespace Itx\Orm;

use \Itx\Exceptions\DBException;

class DB
{
    static $connections = [];
    public static function tryConnect($connection = "default")
    {
        if (!isset(self::$connections[$connection])) {
            $database    = config("database.*");

            if ($settings =  $database[$database["default_database"]]) {
                $driver = "\Itx\Orm\Drivers\\" . (ucFirst($settings["driver"] ?? 'mysql'));
                return self::$connections[$connection] = new $driver($settings);
            }

            throw new DBException("No database configruation file [database.php] in [bundle/configs/]");
        }
        
        return self::$connections[$connection];
    }
}
