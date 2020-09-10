<?php

namespace Itx\Orm;

interface Repository
{
    public function getAll();
    public function store($data);
    public function getById($id);
    public function getByPrimaryKey($id);
}

abstract class BaseRepository implements Repository
{
    protected $model = null;
    protected $paginate = false;
    protected $with = [];
    protected $filterable = [];
    protected $is_active = null;
    public $latest = false;
    public $pKey = null;
    public function __construct()
    {
        $model = $this->model;

        if (!$model) {
            $model = explode("\\", get_called_class());
            $model = str_replace("Repository", "", $model[count($model) - 1]);
            $model = $this->model = "\\Bundle\\Models\\{$model}";
        }

        if ($this->pKey == null) {
            $this->pKey = (new $model)->getKeyName();
        }
    }
    public function getAll($options = null)
    {
        $model = $this->model;
        $model = $model::where();
        
        if ($options instanceof \Psr\Http\Server\RequestHandlerInterface) {
            $model = $this->filterableQuery($model, $options->getQueryParams());
            if (isset($options->getQueryParams()["limit"])) {
                $model = $model->limit($options->getQueryParams()["limit"]);
            }
        } 

        return $model->fetch();
    }

    public function getById($id)
    {
        $model = $this->model;

        return $model::where("id", $id)->first();
    }
    public function getByPrimaryKey($value)
    {
        $model = $this->model;
        $p_key = $model->getKeyName();

        return $model::where($p_key, "=", $value)->first();
    }

    public function store($data)
    {
        $model = $this->model;

        if ($data = $model::create($data)) {

            return $data;
        }

        return null;
    }
    public function __call($method, $args)
    {
        // $this->trip->getByIsExists( true ) ;
        // Trip::where("is_exists" , true)->get() ;

        $model = $this->model;


        if (strpos($method, "getBy") === 0) {
            return $model::where(sprintf("%s = ?" , str(str_replace("getBy", "", $method))->snakeCase()), $args[0])->first();
        }

        if (strpos($method, "getAllBy") === 0) {
            return $model::where( sprintf("%s = ?" , str(str_replace("getAllBy", "", $method))->snakeCase()) , $args[0]  )->fetch();
        }

        if (strpos($method, "deleteBy") === 0) {
            $element = $model::where(
                sprintf("%s = ?" , str(str_replace("deleteBy", "", $method))->snakeCase()) ,
                $args[0]
            )->first();

            if ($element) {
                return $element->delete();
            }
        }

        if (strpos($method, "updateBy") === 0) {

            $element = $model::where(
                sprintf("%s = ?" , str(str_replace("updateBy", "", $method))->snakeCase()) ,
                $args[0]
            )->first();

            if ($element) {
                return $element->update($args[1]);
            }
        }


        return false;
    }


    protected function filterableQuery(&$model = null, $request)
    {
        $query = [];
        $elequent = [];
        $filters = [
            "eq" => "=", "neq" => "!=", "lt" => "<", "lte"  => "<=",
            "gt" => ">", "gte" => ">=", "like" => "LIKE", "beginsWith" => "LIKE", 'endsWith' => "LIKE",
            "between" => "BETWEEN"
        ];

        $replace = ["beginsWith" => "?%", "like" => '%?%', "endsWith" => "%?"];
        
        if ($this->filterable) {
            if ($query = $request["filter"] ?? false) {
                foreach ($query as $key => $value) {
                    $data = explode("_", $key);
                    if (count($data) > 1) {
                        // filter[created_at_gt] = 'something'  ;
                        // where( 'created_at' , '>' , 'something' )
                        $filter_key = $data[count($data) - 1];
                        $filter = isset($filters[$filter_key]) ? $filter_key : "eq";
                        $column = str_replace("_{$filter}", "", $key);
                    } else {
                        $filter = "eq";
                        $column = $key;
                    }
                    if (in_array($column, $this->filterable)) {
                        if ($value !== ".*") {
                            $filterableValue = isset($replace[$filter])  ? str_Replace("?",  $value, $replace[$filter]) : $value;
                            if (method_exists($this, "{$column}Filter")) {
                                $method = "{$column}Filter";
                                $model = $this->{$method}($model,  $filters[$filter], $filterableValue);
                            } else {
                                $elequent = sprintf("`%s` %s ?", $column,  $filters[$filter], $filterableValue);
                                $model = $model->where($elequent,  $filterableValue);
                            }
                        }
                    }
                }
            }
        }

        return $model;
    }
}
