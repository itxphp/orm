<?php

namespace Itx\Orm;

class QueryBuilder
{
	private $prepared = "";
	private $query    = [];

	public function __construct($query = [])
	{
		$this->query = $query;
	}

	public function query()
	{
		return $this->prepared;
	}


	public function select()
	{
		$hasJoins = false;

		$query  = "SELECT";
		$what   = isset($this->query["select"]) ? $this->query["select"] : "*";

		if (!isset($this->query["table"])) {
			throw new \Itx\Exceptions\DBException(
				"You try to build a select query without a table !"
			);
		}

		if (is_array($what)) {
			foreach ($what as $key => $value) {
				$q[$key]	= str_replace("`{{@}}`.", "", "`" . implode("`.`", array_pad(explode(".", str_replace("`", "", $value)), -2, "{{@}}")) . "`");
				$q[$key]   .= !is_numeric($key) ? " AS `{$key}`" : "";
			}

			$query .= " " . implode(" , ", $q) . " ";
		} else {
			$query .= " {$what} ";
		}


		$query .= " FROM `{$this->query['table']}` ";


		$joins = ["innerJoin" => "INNER JOIN", "leftJoin" => "LEFT JOIN", "outerJoin" => "OUTER JOIN", "crossJoin" => "CROSS JOIN"];

		foreach ($joins as $join => $joinQuery) {
			if (isset($this->query[$join])) {
				$hasJoins = true;

				foreach ($this->query[$join] as $table => $joins) {
					if (is_string($joins)) {
						$query .= "{$joinQuery} $joins ";
					} else {
						$join = current($joins);
						$key = key($join);
						$value = $join[$key];
						$query .= " {$joinQuery} {$table} ON {$key} = {$value} ";
					}
				}
			}
		}

		if (isset($this->query["where"]) && !empty($this->query["where"])) {


			$where = [];

			$query .= $hasJoins ? " AND " : " WHERE ";

			if (is_array($this->query["where"])) {
				foreach ($this->query["where"] as $key => $value) {
					if ($value == ".*")  continue;

					$where[] = is_int($key) ? $value : ($key . " = " . ($this->isString($value) ?  "'" . $value . "'" : $value));
				}


				/**
				 *  Replace any '?' or "?" with just ?
				 *  and replace any '%?%' with just %?%
				 */
				$query .= preg_replace("/[\"\']{1,}(\%?\?%?)[\"\']{1,}/", "\$1", implode(" AND ", $where));
			} else {
				$query .= " {$this->query['where']} ";
			}
		}
		if (isset($this->query["orderBy"])) {
			$query .= " ORDER BY {$this->query["orderBy"]} ";
		}
		if (isset($this->query["groupBy"])) {
			$query .= " GROUP BY {$this->query["groupBy"]} ";
		}

		if (isset($this->query["limit"])) {
			$query .= " LIMIT " . implode(",", (array) $this->query["limit"]);
		}

		return $query;
	}
	public function insert($table, $values)
	{
		$query = "";
		if (isset($values[0]) && is_array($values[0])) {
			$data = $values;
		} else {
			$data[] = $values;
		}

		$values = [];
		foreach ($data as $datum) {
			if ($datum == null) continue;

			$partOne = $partThree = "";
			foreach ($datum as $key => $value) {
				$partOne .= "`$key`,";

				if ($this->isString($value)) {

					$partThree .=  "\"" . $value . "\",";
				} else {
					$partThree .= $value . ",";
				}
			}

			$values[] = "(" . subStr($partThree, 0, -1) . ")";
		}

		return "INSERT INTO `{$table}` (" . subStr($partOne, 0, -1) . ") VALUES " . implode(" , ", $values) . " ;";
	}
	public function update($table, $xdata, $condetions)
	{
		$query = "";
		if (isset($xdata[0]) && is_array($xdata[0])) {
			$data = &$xdata;
		} else {
			$data[] = &$xdata;
		}
		foreach ($data as $datum) {
			$query .= "UPDATE `{$table}` SET ";
			$parts = [];
			$replace = [];
			if (is_array($datum)) {
				foreach ($datum as $key => $value) {
					$replace["@" . $key] = $value;
					$parts[] = (is_int($value) || preg_match("/^[a-zA-Z\_\-]{2,}\(.*\);?$/", $value) ||  preg_match("/^`.*`+[\+\-\*\%\/][0-9]{1,}$/", $value) || preg_match("/^\@[a-z]{1,}/", $value)) ? " `{$key}` = {$value} " : "`{$key}` = \"" . $value . "\" ";
				}
			}

			$query .= implode(" , ", $parts);
			if (isset($condetions) && count($condetions) > 0) {
				$query .= " WHERE ";
				$where = [];
				if (is_array($condetions)) {
					foreach ($condetions as $key => $value) {
						if ($value == ".*")  continue;
						$where[] = is_int($key) ? $value : ("`" . $key . "` = " . (is_int($value) || preg_match("/[a-zA-Z\_\-]{2,}\(.*\)/", $value) ||  preg_match("/^`.*`+[\+\-\*\%\/][0-9]{1,}$/", $value) ? $value  : "'" . $value . "'"));
					}


					$query .= implode(" AND ", $where) . (isset($condetion) ? " $condetion " : "");
				} else {
					$query .= " {$condetions} ";
				}
			}
			$query .= " ; \r\n";
		}
		return $query;
	}
	public function patch($array)
	{
	}

	public function delete()
	{
		$hasJoins = false;
		$query  = "DELETE ";

		if (!isset($this->query["table"])) {
			throw new \Itx\Exceptions\DBException(
				"You try to build a delte query without a table !"
			);
		}
		$query .= " FROM `{$this->query['table']}` ";

		$joins = ["innerJoin" => "INNER JOIN", "leftJoin" => "LEFT JOIN", "outerJoin" => "OUTER JOIN", "crossJoin" => "CROSS JOIN"];

		foreach ($joins as $join => $joinQuery) {
			if (isset($this->query[$join])) {
				$hasJoins = true;

				foreach ($this->query[$join] as $table => $joins) {
					list($key, $value) = each($joins);

					$query .= " {$joinQuery} {$table} ON {$key} = {$value} ";
				}
			}
		}

		if (isset($this->query["where"]) && !empty($this->query["where"])) {


			$where = [];

			$query .= $hasJoins ? " AND " : " WHERE ";

			if (is_array($this->query["where"])) {
				foreach ($this->query["where"] as $key => $value) {
					if ($value == ".*")  continue;

					$where[] = is_int($key) ? $value : ($key . " = " . ($this->isString($value) ?  "'" . $value . "'" : $value));
				}

				/**
				 *  Replace any '?' or "?" with just ?
				 *  and replace any '%?%' with just %?%
				 */
				$query .= preg_replace("/[\"\']{1,}(\%?\?%?)[\"\']{1,}/", "\$1", implode(" AND ", $where));
			} else {
				$query .= " {$this->query['where']} ";
			}
		}

		// if(isset($this->query["orderBy"]) )
		// {
		// 	$query.=" ORDER BY {$this->query["orderBy"]} ";
		// }


		// if(isset($this->query["limit"]))
		// {
		// 	$query .= " LIMIT " . implode(","  , (array) $this->query["limit"]) ;
		// }

		return $query;
	}

	private function isString($value)
	{
		return !(is_numeric($value) ||
			preg_match("/^[a-zA-Z\_\-]{2,}\(.*\);?$/", $value) ||
			preg_match("/^`.*`+[\+\-\*\%\/][0-9]{1,}$/", $value) ||
			preg_match("/^\@[a-z]{1,}/", $value));
	}
}
