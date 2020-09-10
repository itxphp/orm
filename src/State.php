<?php

namespace Itx\Orm;

class State implements \Iterator, \ArrayAccess, \JsonSerializable, \Countable
{
    protected $type ;
    protected $model = null;
    protected $position = 0;
    protected $results = [];

    public function __construct($model, $results , $type = 'collection' , $relations = [])
    {
        $this->model = $model;
        $this->results = $results ;
        $this->type  = $type;
        $this->relations = $relations ;
    }
    public function setRelations($relations)
    {
        $this->relations = $relations;
        return $this;
    }
    /**
     * @param string $offset
     * @return bool
     */
    function offsetExists($offset)
    {
        return isset( $this->results[$offset]);
    }
    /**
     * @param string $offset
     * @return mixed
     */
    function offsetGet($offset)
    {
        return new Self($this->model , $this->results[$offset] , "row");

    }
    /**
     * @param string $offset
     * @param mixed $value
     */
    function offsetSet($offset, $value)
    {
        return  $this->results[$offset]  = $value;
    }
    /**
     * @param string $offset
     */
    function offsetUnset($offset)
    {
    }
    // IteratorAggregate
    /**
     * @return \ArrayIterator
     */

    public function rewind()
    {
       return $this->position = 0;
    }

    public function current()
    {
        return new Self($this->model , $this->results[$this->position] , "row");
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        return ++$this->position;
    }

    public function valid()
    {
        return   $this->results[$this->position] ?? false;
    }
    public function __call($method, $args)
    {
        // throw new Exceptions\DBException("Relation does not exist's");

        // check if parent call 
        if(method_exists(get_parent_class($this->model) , $method)) {
           
            return  $this->model->{$method}(...$args) ;   
        }
      
        if(method_exists($this->model , $method)) {

            $ref = $this->model->{$method}(...$args);

            extract($ref);
    
            return $model::where("{$foreign_key} in ?" , array_column($this->results , $local)) ;   
        }
    }

    public function __get($key)
    {
        // if key in results ;

        if(is_string($key) && isset($this->results[$key])) {
            return $this->results[$key];
        }


        
        return $this->model->getRelation( $key  , $this->results , $this->type );        
    }

    public function __destruct()
    {
        return json_encode( $this->results);
    }


    public function __toString()
    {
        return json_encode( $this->results);
    }

    public function jsonSerialize()
    {
        return  $this->results;
    }

    public function dump()
    {
        var_dump( $this->results);
    }

    public function toArray()
    {
        return  $this->results;
    }

    public function count()
    {
        return count( $this->results);
    }


    // public function update($data)
    // {
    //     if($this->type == 'empty')  {
    //         return false ;
    //     }

    //     return $this->prepareModel()->update($data) ;
    // }

    // public function insert($data)
    // {

    //     dd($this->relations);
    //     $model = $this->model ;
    //     $data[ $this->relations[0] ] = $this->relations[1]; 
    //     $model::model()->insert($data);
    // }

    // public function delete()
    // {
    //     if($this->type == 'empty')  {
    //         return false ;
    //     }

    //     return $this->prepareModel()->delete() ;
    // }

    // private function prepareModel()
    // {
    //     $model = $this->model ;
    //     $key = $this->model->getKeyName() ;

    //     if($this->type == "collection") {
    //         $condetion = "{$key} IN ?" ;
    //         if(isset($this->results[0][$key])) {
    //             $binding = array_column( $this->results , $key ) ;
    //         } else if(isset($this->results[0]["itx_model_unique_id"])) {
    //             $binding = array_column( $this->results , "itx_model_unique_id" ) ;
    //         } else {
    //             throw new \Itx\Orm\Exceptions\DBException("No unique id") ;   
    //         }
    //         return $model::where( $condetion , $binding) ;
    //     } else {
    //         $condetion = "{$key} = ?" ;
    //         if($binding = $this->results[ $key ] ?? $this->results["itx_model_unique_id"] ?? false) {

    //             return $model::where( $condetion ,$binding ) ;
    //         }

    //         throw new \Itx\Orm\Exceptions\DBException("No unique id") ;
    //     }
    // }
}
