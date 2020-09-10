<?php
namespace Itx\Orm\Migration;

class Column
{
    protected $table = null ;
    protected $name = null ;
    protected $type = null ;
    protected $options = [] ;
    protected $index = null;

    public function __construct($table , $column , $type , $options = [])
    {
        $this->table = $table ;
        $this->column = $column ;
        $this->type = $type ;
        $this->options = $options ;
        $this->indexes = [] ;

    }
    public function identity()
    {
        $this->options["identity"] = true ;
        return $this ;
    }
    public function nullable($nullable)
    {
        $this->options["null"] = $nullable ;
        return $this;
    }
    public function default($value)
    {
        $this->options["default"] = (int) $value ;
        return $this;
    }
    public function unique($value = true )
    {
        $this->options["unique"] = $value ;
        return $this;
    }
    public function comment($value = true )
    {
        $this->options["comment"] = $value ;
        return $this;
    }
    public function signed($value = true )
    {
        $this->options["signed"] = $value ;
        return $this;
    }
    public function unsigned()
    {
        $this->options["signed"] = false ;
        return $this;
    }

    public function index($index = true )
    {
        $this->index = $index ;
    }
    public function __destruct()
    {
        $this->table->addColumn(
            $this->column , $this->type ,  $this->options
        ) ;

        if($this->index) {
            $this->table->addIndex($this->column);
        }
    }
}