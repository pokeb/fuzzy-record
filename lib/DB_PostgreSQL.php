<?php

class DB extends DBBase {

	static protected $db_connection;
	static public $db_quote_mark = '"'; 
	static public $db_ilike_operator = "ilike";
	static protected $DSN_prefix = "pgsql";
	static protected $charset_sql = "set names 'UTF8'";
	
	static protected function limit($offset,$num_rows) {
		$limit_sql = "";
		if ($offset > -1 && $num_rows > -1) {
			$limit_sql = " offset $offset limit $num_rows";
		} elseif ($num_rows > -1) {
			$limit_sql = " limit $num_rows";
		}
		return $limit_sql;
	}
	
	static public function last_insert_id($sequence="") {
		return static::$db_connection->lastInsertId($sequence);
	}
	
	/*
	static protected function db_field_type_for_field($object,$field) {
		$class = get_class($object);
		if (!key_exists($field,$class::$properties)) {
			throw new FuzzyRecordException("Property '$field' not found");
		}
		$options = $class::$properties[$field];
		if (in_array("bool",$options)) {
			return "boolean";
		} elseif (in_array("date_with_time",$options)) {
			return "timestamp without timezone";
		} elseif (in_array("time_with_timezone",$options)) {
			return "time with timezone";	
		} elseif (in_array("date_with_time_and_timezone",$options)) {
			return "timestamp with timezone";
		}
		return DBBase::db_field_type_for_field($object,$field);
	}
	*/
	
	static public function create_table_sql_for_class($class) {
		$index_sql = "";
		$sql = "create table ".$class::table_name()." (\r\n";
		$i=0;
		foreach ($class::properties() as $name => $options) {
			if ($i > 0) {
				$sql .= ",\r\n";
			}
		
			$sql .= DB::$db_quote_mark.$name.DB::$db_quote_mark." ";
			switch ($class::field_type($name)) {
				case "boolean":
					$sql .= "bool ";
					break;
				case "sorter":
					$sql .= "integer ";
					break;
				case "integer":			
					$sql .= "integer ";
					if (in_array("auto_increment",$options)) {
					


						$index_sql .= "CREATE SEQUENCE ".$class::table_name()."_".$name."_seq INCREMENT BY 1 NO MAXVALUE NO MINVALUE CACHE 1;\r\n";
						$index_sql .= "alter SEQUENCE ".$class::table_name()."_".$name."_seq owned by ".$class::table_name().".$name;\r\n";
						$index_sql .= "alter table ".$class::table_name()." alter column $name set default nextval('".$class::table_name()."_".$name."_seq'::regclass);\r\n";
					}
					break;
				case "email_address":
					$sql .= "varchar ";
					break;
				case "date":
					$sql .= "date ";
					break;
				case "time":
					$sql .= "time ";
					break;
				case "date_with_time":
					$sql .= "timestamp without time zone ";
					break;
				case "date_with_time_and_timezone":
					$sql .= "timestamp with time zone ";
					break;				
				case "time_with_timezone":
					$sql .= "time with time zone ";
					break;
				case "varchar":
					$length = 255;
					if (key_exists("max_length",$options)) {
						$length = $options["max_length"];
					}
					$sql .= "varchar($length) ";
					break;
				case "enum":
					$sql .= "varchar ";
					break;
				default:
					$sql .= "text ";
			}
			if (in_array("required",$options)) {
				$sql .= "not null ";
			}
			$default = $class::default_value_for($name);
			if ($default != "") {
				$sql .= "default ".DB::escape($default);
			}
			$i++;
		}
		$sql .= "); ";
		if (count($class::primary_keys()) > 0) {
			$sql .= "alter table only ".$class::table_name()." add constraint ".$class::table_name()."_pkey primary key (".DB::$db_quote_mark.implode(DB::$db_quote_mark.",".DB::$db_quote_mark,$class::primary_keys()).DB::$db_quote_mark.");";
		}
		$sql .= " $index_sql";
		return $sql;
	}

}
