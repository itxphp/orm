<?php

namespace Itx\Orm;

class Row implements \ArrayAccess, \IteratorAggregate, \JsonSerializable
{
	protected $data = [];
	protected $model = null;
	protected $reference = null;
	protected $relations = [] ;
	public function __construct($data, $model, $relations = [])
	{

		$this->data  = $data;
		$this->model = $model;
		$this->relations = $relations;

		$this->reference = $this->data[$this->model->pKey] ?? $this->data["itx_model_unique_id"] ?? null;

		if (isset($this->data["itx_model_unique_id"])) {
			unset($this->data["itx_model_unique_id"]);
		}
	}

	public function setRelations($relations)
	{
		$this->relations = $relations;
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
			$data = $this->model->setReference($this->reference, $this->data, true)->{$method}(...$args);
			if (is_array($data) && isset($data["is_associated"])) {
				extract($data);
				return  $model::where("{$local} = ?", $this->data[$foreign_key]);
			}

			return $data;
		}
	}

	public function __get($key)
	{
		if (method_exists($this->model, $key)) {
			$data = $this->model->setReference($this->reference, $this->data, true)->{$key}();
			if (is_array($data) && isset($data["is_associated"])) {
				extract($this->model->{$key}());
				$model = $model::where("{$local} = ?", $this->data[$foreign_key]);
				return $relation == "hasOne" ? $model->first() : $model->fetch();
			}
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
		return $this->data;
	}
}
