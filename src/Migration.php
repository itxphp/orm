<?php
namespace Itx\Orm;
use Phinx\Migration\AbstractMigration;
use Itx\Orm\Migration\{Table , Trigger} ;

class Migration extends AbstractMigration 
{
    public $ifExistsDrop = false ;

    public function ifExistsDrop($tableName)
    {
        if($this->hasTable($tableName)) {
            return $this->table($tableName)->drop()->save();
        }
    }

    public function table($tableName, $options = [])
    {
        $table = new Table($tableName, $options, $this->getAdapter());
        $this->tables[] = $table;
        return $table;
    }


    public function drop($table)
    {
        $this->table($table)->drop()->save();
    }

    public function trigger($name)
    {
        return new Trigger( $name  , $this);
    }
}
