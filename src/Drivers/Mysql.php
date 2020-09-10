<?php

namespace Itx\Orm\Drivers;

use \Itx\Exceptions\DBException;

class Mysql
{
    protected $handler = null;
    protected $transaction = false;
    protected $transactionQuery = [];
    protected $vaild = ["assoc" => "mysqli_fetch_assoc", "array" => "mysqli_fetch_array", "object" => "mysqli_fetch_object"];
    public function __construct($data)
    {
        if ($this->link = @mysqli_connect($data["host"] == "localhost" ? "127.0.0.1" : $data["host"], $data["user"], $data["pass"], $data["name"])) {
            if (isset($data["charset"])) {
                $this->query = "SET NAMES '" . $data["charset"] . "'";

                if (!mysqli_query($this->link, $this->query)) {
                    throw new  DBException(mysqli_error($this->link));
                }
            }
        } else {
            throw new  DBException("Can't connect to DB using provided info check [Bundle/configs/database.php]");
        }
    }
    public function startTransaction($flag = 0)
    {
        return mysqli_begin_transaction($this->link);
    }
    public function commit()
    {
        return mysqli_commit($this->link);
    }
    public function rollback()
    {
        return mysqli_rollback($this->link);
    }
    public function link()
    {
        return $this->link;
    }

    public function query($query, $bind = [])
    {

        $this->query = $query;
        if ($statment = mysqli_prepare($this->link, $query)) {
            if (($bind  = (array) $bind)) {
                $binding = "";
                foreach ($bind as $var) {
                    if (is_array($var)) {
                        foreach($var as $test) {
                            $type = gettype($test)[0] ?? 's';
                            $binding .= (!in_array($type, ["i","d","s"] )) ? "s" : $type;
                        }
                    } else {
                        $type = gettype($var)[0] ?? 's';
                        $binding .= (!in_array($type, ["i","d","s"] )) ? "s" : $type;
                    }
                }


                $bindings = [];

                foreach ($bind as $bind) {
                    if (is_array($bind)) {
                        $bindings = [...$bindings, ...$bind];
                    } else {
                        $bindings  = [...$bindings, $bind];
                    }
                }



                $bindings && mysqli_stmt_bind_param($statment, $binding, ...$bindings);
            }
            mysqli_stmt_execute($statment);


            $this->handler = mysqli_stmt_get_result($statment);

            mysqli_stmt_close($statment);

            return $this;
        }

        throw new DBException(mysqli_error($this->link) . " [query] " . $query,   mysqli_errno($this->link), ["query" => $query]);
    }
    public function count()
    {
        return  $this->handler == null ? 0 : mysqli_num_rows($this->handler);
    }
    public function countLatestResults()
    {
        return mysqli_num_rows($this->handler);
    }
    public function fetch($type = "object")
    {
        if (!$this->handler) return null;
        return $this->vaild[isset($this->vaild[$type]) ? $type : "object"]($this->handler);
    }

    public function lastInsertId()
    {
        return mysqli_insert_id($this->link);
    }

    public function lastQuery()
    {
        return $this->query;
    }

    public function throwIfError()
    {
        mysqli_errno($this->link);
    }
}
