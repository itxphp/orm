<?php

namespace Itx\Orm;

use \Itx\Orm\Exceptions\DBException;
use \Itx\Orm\{DB, QueryBuilder, Row};
use Itx\Utilities\Str;

class Model
{

	public $pKey 		= "id";
	protected $casts = null;
	protected $connection 	= "default";
	protected $where 		= [];
	protected $select 		= "*";
	protected $event		= null;
	protected $localize  = false;
	protected $query	   = [];
	protected $timestamps = ["created" => "created_at", "updated" => "updated_at", "deleted" => "deleted_at"];
	protected $softDelete = false;
	protected $data = [];
	protected $table		= null;
	private $reference = null;
	public $action = "select";
	private $enableCache = false;
	private $eagered = [];

	public function __construct($eager = [])
	{
		$this->onCreate();

		$this->values = [];

		$this->enableCache = $this->enableCache ?? false;

		$this->table = $this->table ?? $this->getModelName();

		$this->query = [
			"select"    => $this->select,
			"table"     => $this->table,
			"where"     => $this->where,
			"limit"     => null,
			"orderBy"   => null,
			"groupBy"   => null,
		];


		$joins = ["innerJoin", "leftJoin", "outerJoin", "crossJoin"];

		foreach ($joins as $join) {
			if (isset($this->{$join})) {
				$this->query[$join] = $this->{$join};
			}
		}

		if ($this->localize) {
			$this->localeTable = $this->table . "_translations";
			$this->setQuery("innerJoin", [$this->localeTable => [
				$this->table . "." . $this->pKey => $this->localeTable . "." . $this->table . "_" . $this->pKey
			]]);
		}


		$this->eager = $eager;

		$this->onCreate();
	}

	protected function mapper($data)
	{
		return [$this->table => $data];
	}

	protected function onCreate()
	{
		return null;
	}

	public function getTableName()
	{
		return $this->table;
	}

	public function getKeyName()
	{
		return $this->pKey;
	}

	public function getDB($connection = null)
	{
		return DB::tryConnect($connection ?: $this->connection);
	}

	public function getModelName()
	{
		return strtolower(substr(strrchr(get_class($this), '\\'), 1));
	}

	public function getAction()
	{
		return $this->action;
	}

	private function setItxUnique()
	{
		if ($this->query["select"] != "*") {
			$this->query["select"] =  $this->query["select"] . "," . $this->pKey . " AS itx_model_unique_id ";
		}

		return $this;
	}

	public function hasOne($model, $foreign = null, $local = null)
	{
		return $this->setAssociations(__FUNCTION__, $model, $foreign, $local);
	}


	public function belongsTo($model, $foreign = null, $local = null)
	{
		return $this->setAssociations( __FUNCTION__ , $model, $foreign, $local);
	}

	public function hasMany($model, $foreign = null, $local = null)
	{
		return $this->setAssociations(__FUNCTION__, $model, $foreign, $local);
	}


	private function setAssociations($relation, $model, $foreign = null, $local = null)
	{

		return   [
			"model" => is_object($model) ? get_class($model) : $model,
			"relation" => $relation,
			"foreign_key" => $foreign,
			"local" => $local,
			"is_associated" => true
		];
	}


	/**
	 * --------------------------------------------------
	 * return last_insert_id if insertion success , false if not succcess
	 * ---------------------------------------------------
	 */
	public function insert($data = [])
	{
		$queryBuilder = new QueryBuilder([]);
		$query = $queryBuilder->insert($this->table, $data);
		if ($this->getDB()->query($query, [])) {
			return $this->refetch();
		}


		return false;
	}




	/**
	 *  Truncate model 
	
	 */
	public static function truncate()
	{
		return ($self = new static())->getDB()->query("TRUNCATE {$self->table}");
	}



	/**
	 * 
	 * --------------------------------------------------
	 * return row if exists in the table otherwise return null 
	 * if( $content = Content::exists( 10 ) )
	 * {	
	 * 		echo $content->title ;
	 * 		$content->increament("visits" , 1) ;
	 * }
	 * 
	 *  $content = Content::exists(10) or abort(404) ;
	 * ---------------------------------------------------
	 */
	public static function exists($where = null, $replace = [])
	{
		if ($where == null) {
			return null;
		}

		return self::where($where, $replace)->first();
	}


	public static function model()
	{
		return new static();
	}

	public static function with($relation)
	{
		$self = new static;

		if (method_exists($self, $relation)) {
			$self->eager = $self->{$relation}();
			$self->eager["name"] = $relation;
			return $self;
		}

		throw new DBException("Relation [ $relation ] not defiend in " . static::class);
	}

	public function setValues($values)
	{
		$this->values = array_merge($this->values, $values);
		return $this;
	}
	private function callWhere(): self
	{
		$values = func_get_args();
		$where = array_shift($values);
		if ($where === ".*") {
			return $this;
		}

		if (is_int($where)) {
			$this->values = [(int) $where];
			return $this->setQuery("where", "{$this->pKey} = ?");
		}

		["values" => $values, "query" => $where] = $this->prepareWhere($where, $values);

		$this->setValues($values);

		return $this->setQuery("where", is_array($where) ? $where : (string) $where);
	}

	public function select($what)
	{
		return $this->setQuery("action", "select")->setQuery("select", $what)->orderBy("{$this->pKey}");
	}
	public function orderBy($order)
	{
		if (count($exploded = explode(".", $order)) == 1) {
			$order = $this->table . "." . $order;
		}
		return $this->setQuery("orderBy", $order);
	}
	public function groupBy($order)
	{
		return $this->setQuery("groupBy", $order);
	}
	public function innerJoin($what, $with)
	{
		return $this->setQuery("innerJoin", [$what => $with]);
	}
	public function leftJoin($what, $with)
	{
		return $this->setQuery("leftJoin", [$what => $with]);
	}

	public function limit($limit, $offset = null)
	{
		return $this->setQuery("limit", $offset == null ? $limit : [$limit, $offset]);
	}

	protected function counter()
	{
		return null;
	}

	public function count()
	{
		if (($value = $this->counter()) === null) {
			if ($fetch = $this->select("COUNT(1) AS `itx_model_data_count`")->first(1)) {
				return $fetch->itx_model_data_count ?? 0;
			}

			return 0;
		}

		return $value;
	}

	public function min($what = null, $row = true)
	{

		$what = $what ?: $this->getKeyName();
		if ($row) {
			if ($fetch = $this->orderBy("{$what} ASC")->limit(1)->first()) {
				return $fetch;
			}
		}

		if ($fetch = $this->select("MIN($what) AS `itx_model_min_value`")->first(1)) {
			return $fetch->itx_model_min_value;
		}

		return null;
	}

	public function max($what = null, $row = true)
	{
		$what = $what ?: $this->getKeyName();
		if ($row) {
			if ($fetch = $this->orderBy("{$what} DESC")->limit(1)->first(1)) {
				return $fetch;
			}
		}


		if ($fetch = $this->select("MAX($what) AS `itx_model_max_value`")->first(1)) {
			return  $fetch->itx_model_max_value;
		}

		return null;
	}
	public function sum($what = null)
	{
		$what = $what ?: $this->getKeyName();

		if ($fetch = $this->select("SUM($what) AS `itx_model_sum_value`")->first(1)) {
			return (int) $fetch->itx_model_sum_value;
		}

		return null;
	}
	public function avg($what = null)
	{
		$what = $what ?: $this->getKeyName();

		if ($fetch = $this->select("AVG($what) AS `itx_model_avg_value`")->first(1)) {
			return (float) $fetch->itx_model_avg_value;
		}
		return null;
	}
	public function paginate($type = "object")
	{
		$data = $this->getDB()->query($this->buildQuery(), $this->values);
		while ($fetch = $data->fetch("assoc")) {;
		}
	}

	public function yield($type = "object")
	{
		$data = $this->getDB()->query($this->setItxUnique()->buildQuery(), (array) $this->values);

		try {
			while ($fetch = $data->fetch("assoc")) {
				yield $fetch;
			}
		} finally {
		}
	}

	public function first($limit = 1)
	{
		return $this->limit($limit)->fetch();
	}
	public function last($limit = 1)
	{
		return $this->orderBy($this->query["orderBy"] ?? $this->pKey . " DESC")->limit($limit)->fetch();
	}

	public function fetch($type = "assoc")
	{
		$results = [];
		$this->setItxUnique();
		$data = $this->getDB()->query($this->buildQuery(), $this->values);
		while ($fetch = $data->fetch("assoc")) {
			$results[] = $fetch;
		}


		$relations = [];

		if ($this->eager) {
			if ($results) {
				$model = $this->eager["model"];
				$keys = array_column($results, $this->eager["local"]);
				$relations = [];
				$data = (new $model)->where($this->eager["foreign_key"] . " IN ? ", $keys)->yield();

				foreach ($data as $datum) {
					
					if (isset($relations[$datum[$this->eager["foreign_key"]]]) && is_array($relations[$datum[$this->eager["foreign_key"]]])) {
						$relations[$datum[$this->eager["foreign_key"]]][] = $datum;
					} else {
						$relations[$datum[$this->eager["foreign_key"]]] = [$datum];
					}
				}
			}

			$this->eagered = [
				$this->eager["name"] => $relations
			];
		}

		return $results ? new State(
			$this,
			$this->query["limit"] == 1  ? $results[0] : $results,
			$this->query["limit"] == 1 ? 'row' : 'collection'
		) : [];
	}
	public function refetch()
	{


		if ($this->reference == null) {
			return $this->fetch();
		}


		return  self::where("{$this->pKey} = ?", $this->reference)->first(1);
	}

	/**
	 * --------------------------------------------------------------------
	 *  
	 * --------------------------------------------------------------------
	 */
	public function exec()
	{
		return $this->getDB()->query(
			$this->buildQuery($this->action),
			$this->values
		);
	}


	public function update($data = [])
	{
		$queryBuilder = new QueryBuilder([]);
		$query = $queryBuilder->update($this->table, $data, $this->query["where"]);

		if ($this->getDB()->query($query, $this->reference ? []  : $this->values)) {
			return $this->refetch();
		}

		return false;
	}


	public function addTime($to, $interval)
	{
		return $this->update([
			$to => "DATE_ADD(`{$to}` , INTERVAL {$interval})"
		]);
	}
	public function increase($to, $number = 1)
	{
		return $this->update([
			$to => "`{$to}`+{$number}"
		]);
	}
	public function decrease($to, $number = 1)
	{
		return $this->update([
			$to => "`{$to}`-{$number}"
		]);
	}
	public function delete()
	{
		$this->setQuery("action", "delete")->exec();
	}


	public function dump()
	{
		return $this->buildQuery($this->action);
	}

	public function setQuery($key, $value, $only = false)
	{
		if ($key == "action") {
			$this->action = $value;
			return $this;
		}

		if ($key == 'select') {

			$this->query["select"] = $value;
			return $this;
		}

		if ($key == "where") {

			$this->query["where"][] =  $value;
			return $this;
		}

		if ($key == "whereOnly") {

			$this->query["where"] =  [$value];
			$this->values = [];
			return $this;
		}

		$this->query[$key] =  $value;
		return $this;
	}

	public function setReference($ref = null, &$data = [])
	{
		if ($ref == null) {
			throw new DBException(" 
				No reference id , please verfiy that you defiend table primary key 
			");
		}

		$this->data = $data;


		$this->reference = $ref;

		return $this;
	}


	protected function getQuery($key = null)
	{
		return $key == null ? $this->query : ($this->query[$key] ?? null);
	}

	protected function restoreQuery($query)
	{
		$this->query = $query;
		return $this;
	}

	protected function buildQuery($as = "select")
	{
		return (new QueryBuilder($this->query))->{$as}();
	}


	public function locale(String $locale)
	{
		return $this->setQuery("where", ["locale" => $locale]);
	}



	public function isAssociation($method)
	{
		return $this->association[$method] ?? null;
	}





	protected function magicCall($data, $args, $action)
	{

		$data = $this->where(strtolower($data) . "= ?", $args[0])->first();

		if ($action == "update") {

			$data->update($args[1]);

			return $data->refetch();
		}


		if ($action == "delete") {
			return $data->delete();
		}


		return $data;
	}


	private function prepareWhere($query, $values)
	{
		$values = (array) $values;

		/**
		 *  Model::where("username LIKE ?% AND `role` IN ?" , $username , ["admin" , "moderator"])->fetch();
		 *  Replace ?% with value $value."%" etc.. 
		 *  and replace IN ? with IN (? , ?) ;

		 */
		preg_match_all("/((in\s+)?[\"\']?\%?\?\%?[\"|\']?)/i", $query, $out);
		foreach ($out[1] as $key => $search) {
			if (strpos($search, "%") > -1) {
				$values[$key] = str_replace(["\"", "'"], "", str_replace("?", $values[$key], $search));
			}

			if (stripos($search, "in ") > -1) {
				$placeholders = implode(',', array_fill(0, count($values[$key]), '?'));
				$query = str_replace($search, "IN ({$placeholders})", $query);
			}
		}
		$query = preg_replace("/([\"\']?\%?\?\%?[\"|\']?)/", "?", $query);

		$query = str_ireplace(["!= null", " = null ", "!in"], ["IS NOT NULL", "IS NULL ", "NOT IN"], $query);



		return ["query" => $query, "values" => $values];
	}

	public function __call($method, $args)
	{
		if ($method == "where") {
			return $this->callWhere(...$args);
		}
	}

	public static function __callStatic($method, $args)
	{
		if ($method == "where") {
			$instance = new static();
			return $instance->callWhere(...$args);
		}
	}

	protected function triggers()
	{
		return null;
	}


	private function eventHandler($event)
	{
		if ($triggers = $this->triggers()) {
		}
	}

	public function __get($key)
	{
		return $this->data[$key] ?? null;
	}

	public function getRelation($relation, $results, $type)
	{

		if (!method_exists($this, $relation)) {
			throw new Exceptions\DBException(
				sprintf("Relation [ %s ]  is'nt defiend in [ %s ]", $relation, self::class)
			);
		}

		$relationDetails = $this->{$relation}();

		if ($this->eagered[$relation] ?? false) {
			if ($type == "collection") {
				$columns = array_column($results, $relationDetails["local"]);
				$return = [];

				foreach ($this->eagered[$relation] as $key => $value) {
					if (in_array($key, $columns)) {
						$return = array_merge($return, $value);
					}
				}


				return new State(
					new static,
					$return,
					$relationDetails["relation"] === 'hasOne' ? 'row' : 'collection'
				);
			} else {
				$key = $results[$relationDetails["local"]];
				if (array_key_Exists($key, $this->eagered[$relation])) {
					return new State(
						new static,
						$this->eagered[$relation][$key],
						$relationDetails["relation"] === 'hasOne' ? 'row' : 'collection'
					);
				}
			}
		} else {

			[
				"model" => $model , 
				"relation" => $relation , 
				"foreign_key" => $foreign ,
				"local" => $local
			] = $this->{$relation}() ;



			if($relation == "belongsTo" || $relation == "hasOne") {
			}

			return $model::where("{$foreign} IN ?"  , array_column($results, $local))->fetch();
		}
	}
}
