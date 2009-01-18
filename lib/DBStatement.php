<?php

class DBStatement {
	
	protected $sql;
	protected $statement;
	protected $db_connection;
	
	public function __construct($db_connection,$sql) {
		$this->db_connection = $db_connection;
		$this->sql = $sql;
		
		//exception handling
		$this->statement = $this->db_connection->prepare($this->sql);
	}
	
	public function bind_value($param,$value) {
		$this->statement->bindValue($param,$value);
	}
	
	public function execute($values=NULL) {
		if ($this->statement->execute($values) === false) {
			throw new FuzzyRecordException("Failed to execute prepared statement '$this->sql'");
		}
	}
	
	public function fetch_assoc() {
		$result = $this->statement->fetch(PDO::FETCH_ASSOC);

		return $result;
	}

}