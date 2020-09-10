<?php

namespace Itx\Orm\Migration;

use Itx\Orm\Migration\Column;
use Phinx\Db\Table as BaseTable;
use Phinx\Db\Adapter\MysqlAdapter;

class Table extends BaseTable
{
    protected $types = [
        "text" => [
            "tiny" => 255,
            "small" => 255,
            "regular" => 65535,
            "medium" => 16777215,
            "long" => 4294967295
        ],
        "blob" => [
            "tiny" => 255,
            "small" => 255,
            "regular" => 65535,
            "medium" => 16777215,
            "long" => 4294967295
        ] ,
        "integer" => [
            "tiny" => 255,
            "small" => 65535,
            "regular" => 4294967295,
            "medium" => 16777215,
            "big" => 18446744073709551615
        ]
    ];

    public function id()
    {
        $this->getTable()->setOptions(["id" => false, "primary_key" => "id"]);

       return $this->integer("id")->unsigned()->identity();
    }

    public function uuid($name = 'uuid')
    {
        return new Column($this, $name , "uuid");
    }

    public function primary($name)
    {
        $this->getTable()->setOptions(["id" => false, "primary_key" => $name]);
    }

    public function tinyint($name)
    {
        return new Column($this, $name, "integer", ['limit' => $this->types["int"]["integer"]]);
    }

    public function integer($name)
    {
        return new Column($this, $name, "integer", ['signed' => false, 'limit' => $this->types["integer"]["regular"] ]);
    }

    public function binary($name)
    {
        return new Column($this, $name, "binary", []);
    }

    public function boolean($name)
    {
        return new Column($this, $name, "boolean", ['signed' => false]);
    }

    public function string($name, $length = 128)
    {
        return new Column($this, $name, "string", ["length" => $length]);
    }
   

    public function timestamp($name)
    {
        return new Column($this, $name, 'timestamp');
    }

    public function text($name)
    {
        return new Column($this, $name, 'text');
    }

    public function enum($name , $values)
    {
        return new Column($this, $name, 'enum' , [ 'values' => $values ]);
    }

    public function timestamps($createdAt = 'created_at', $updatedAt = 'updated_at', $withTimezone = false)
    {
        return $this->addTimestamps($createdAt, $updatedAt, $withTimezone);
    }


    public function __call($method, $args)
    {
    }
}
