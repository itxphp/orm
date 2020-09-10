<?php
namespace Itx\Orm\Drivers;

class PDO
{
    protected $link = null ;
    protected $transaction = false;
    protected $transactionQuery = [];
    public function __construct($data)
    {
        
        $dsn =  $settings["dsn"] ?? "{$settings["driver"]}:dbname={$settings["database"]};host={$settings["host"]}" ;
        $connection = new PDO($dsn , $settings["username"] ?? null , $settings["password"] ?? null) ;
    }
    public function startTransaction()
    {
    }
    public function commit()
    {
        
    }
    public function rollback()
    {
       
    }
	public function link()
	{
	}
    public function query($query , $replace = [])
    {
		
    }
	public function count()
	{

	}
    public function fetch($type = "object")
    {
     
    }
    public function yield($type = "object")
    {
   
    }
    public function insertID()
    {
    }
    public function lastQuery()
    {

    }
}

/*
$db = DB::tryConnect() ;
$db->query("SELECT * FROM `users` WHERE `name` = ?" , ["ahmad"])-;
foreach($data as $db->yieldAll())
{

}
*/