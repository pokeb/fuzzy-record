<?php

class DBStatement {
	
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
		$this->statement->execute($values);
	}
	
	public function fetch_assoc() {
		return $this->statement->fetch(PDO::FETCH_ASSOC);
	}

}