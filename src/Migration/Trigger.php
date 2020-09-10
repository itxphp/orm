<?php
namespace Itx\Orm\Migration;

class Trigger
{
    protected $table = null ;
    protected $migration = null ;

    public function __construct($name , $migration)
    {
        $this->migration = $migration ;
        $this->trigger["name"] = $name ;
    }
    public function on($table)
    {
        $this->trigger["table"] = $table ;
        return $this;
    }
    public function before( $action , $callback )
    {
        $this->trigger["action"] = $action ;
        $this->trigger["when"] = "before" ;
        $this->trigger["sql"] = $callback() ;
        return $this;

    }
    public function after( $action , $callback )
    {
        $this->trigger["action"] = $action ;
        $this->trigger["when"] = "after" ;
        $this->trigger["sql"] = $callback() ;
        return $this;

    }
    public function __destruct()
    {
        $template = "CREATE TRIGGER %s %s %s ON %s %s;" ;

        extract( $this->trigger) ;

        $query = sprintf($template , $name , $when , $action , $table , $sql ); 

        return $this->migration->query($query) ;

    }
}