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
			$limit_sql = " offset $num_rows limit $offset";
		} elseif ($num_rows > -1) {
			$limit_sql = " limit $num_rows";
		}
		return $limit_sql;
	}

}
