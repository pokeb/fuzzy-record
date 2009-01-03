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
	
	static protected function configure_charset() {}

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
	
	static public function last_insert_id() {
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

}