<?php

namespace Itx\Orm;

class RowList implements \ArrayAccess, \IteratorAggregate, \JsonSerializable , \Countable
{
	protected $data = [];
    protected $keys = [];
    protected $model = null ;

	public function __construct($data, $keys  , $model)
	{
        $this->data = $data ;
		$this->keys = $keys ;
		
		$this->model = $model ;
		
	}
	/**
	 * @param string $offset
	 * @return bool
	 */
	function offsetExists($offset)
	{
		return isset($this->data[$offset]);
	}
	/**
	 * @param string $offset
	 * @return mixed
	 */
	function offsetGet($offset)
	{
		return $this->data[$offset] ?? null;
	}
	/**
	 * @param string $offset
	 * @param mixed $value
	 */
	function offsetSet($offset, $value)
	{
		return $this->data[$offset]  = $value;
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
	function getIterator()
	{
		return new \ArrayIterator($this->data);
	}

	public function __call($method, $args)
	{
        // delete , update , patch , increment , decrement , relations 
		if (method_exists($this->model, $method)) {
			
            return $this->model->setQuery("whereOnly" , $this->model->getKeyName() . " IN (". implode("," , $this->keys ).") "   )->setValues([])->{$method}(...$args) ;
		}
	}

	public function __get($key)
	{

		if (method_exists($this->model, $key)) {
            return $this->model->setQuery("whereOnly" , $this->model->getKeyName() . " IN (". implode("," , $this->keys ).") "   )->setValues([])->{$key}() ;
		}

		return $this->data[$key] ?? die('not found');
	}

	public function __destruct()
	{
		return json_encode($this->data);
	}


	public function __toString()
	{
		return json_encode($this->data);
	}

	public function jsonSerialize()
	{
		return $this->data;
	}

	public function dump()
	{
		var_dump($this->data);
	}

	public function toArray()
	{
		return $this->data ; 
    }

    public function count()
    {
        return count($this->data); 
	}

    public function countable()
    {
        return $this->count() ;
    }

}
