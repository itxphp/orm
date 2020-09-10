<?php
namespace Itx\Orm;
interface DriverInterface 
{
    public function tryConnect() ;
    public function query() ; 
    public function count();
    public function fetch() ; 
    public function insertId();
}



