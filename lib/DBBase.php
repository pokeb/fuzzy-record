<?php

class DBBase {

	static protected $transaction_in_progress;

	static protected function db_connect() {
		if (isset(static::$db_connection)) {
			return;
		}
		try {
			$class = get_called_class();
			static::$db_connection = new PDO($class::$DSN_prefix.':host='.DB_SERVER.';dbname='.DB_DATABASE, DB_USER, DB_PASSWORD);
			static::$db_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			throw new FuzzyRecordException("DB connection failed:".$e->getMessage());
		}
		try {
			static::query($class::$charset_sql);
		} catch (PDOException $e) {
			throw new FuzzyRecordException("Error setting charset to UTF8: ".$e->getMessage());
		}
	}

	static public function prepare($sql,$offset=-1,$num_rows=-1) {
		static::db_connect();
		$sql .= static::limit($offset,$num_rows);
		return new DBStatement(static::$db_connection,$sql);
	}
	
	static public function query($sql,$offset=-1,$num_rows=-1) {
		static::db_connect();
		$sql .= static::limit($offset,$num_rows);
		try {
			$result = static::$db_connection->query($sql);
		} catch (PDOException $e) {
			throw new FuzzyRecordException("$sql : DB Error: ".$e->getMessage());
		}
		return new DBResult($result);
	}
	
	static public function last_insert_id($sequence="") {
		return static::$db_connection->lastInsertId();
	}
	
	static public function error_no() {
		return static::$db_connection->errorCode();
	}
	
	static public function escape($what) {
		static::db_connect();
		return static::$db_connection->quote($what);
	}

	static public function rollback() {
		
		$transaction = DBBase::$transaction_in_progress;
		if (isset($transaction)) {
			$transaction->rollback();
		}
		DBBase::$transaction_in_progress = NULL;
	}
	
	static public function start_transaction($implicit=false) {
		$transaction = new DBTransaction();
		$transaction->is_implicit = $implicit;
		$transaction->start();
		DBBase::$transaction_in_progress = $transaction;
	}

	static public function commit() {
		$transaction = DBBase::$transaction_in_progress;
		if (isset($transaction)) {
			$transaction->commit();
		}
		DBBase::$transaction_in_progress = NULL;
	}
	
	static public function start_implicit_transaction() {
		if (!isset(DBBase::$transaction_in_progress)) {
			DBBase::start_transaction(true);
		}
	}

	static public function commit_implicit_transaction() {
		if (isset(DBBase::$transaction_in_progress) && DBBase::$transaction_in_progress->is_implicit) {
			DBBase::commit();
		}
	}
	
	static public function create_table_sql_for_class($class) {
		$sql = "create table if not exists ".$class::table_name()." (\r\n";
		foreach ($class::properties() as $name => $options) {
			$sql .= DB::$db_quote_mark.$name.DB::$db_quote_mark." ";
			switch ($class::field_type($name)) {
				case "boolean":
					$sql .= "tinyint(1) ";
					break;
				case "sorter":
					$sql .= "int(12) unsigned ";
					break;
				case "integer":
					$size = 12;
					if (array_key_exists("size",$options)) {
						$size = $options["size"]; 
					}				
					$sql .= "int($size) unsigned ";
					if (in_array("auto_increment",$options)) {
						$sql .= "auto_increment ";
					}
					break;
				case "email_address":
					$sql .= "varchar(255) ";
					break;
				case "date_with_time":
					$sql .= "timestamp ";
					break;
				case "date":
					$sql .= "date ";
					break;
				case "time":
					$sql .= "time ";
					break;
				case "date_with_time_and_timezone":
				case "time_with_timezone":
					$sql .= "varchar(255) ";
					break;
				case "varchar":
					$length = 255;
					if (key_exists("max_length",$options)) {
						$length = $options["max_length"];
					}
					$sql .= "varchar($length) ";
					break;
				case "enum":
					$sql .= "enum('".implode("','",$options['enum'])."') ";
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
			$sql .= ",\r\n";
		}
		
		if (count($class::primary_keys()) > 0) {
			$sql .= "primary key (".DB::$db_quote_mark.implode(DB::$db_quote_mark.",".DB::$db_quote_mark,$class::primary_keys()).DB::$db_quote_mark.")";
		}
		$sql .= ") ENGINE=InnoDB default charset=utf8 collate=utf8_unicode_ci; ";
		return $sql;
	}

}