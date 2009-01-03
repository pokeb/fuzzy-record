<?php

class DBResult {

	protected $result;
	
	public function __construct($result) {
		$this->result = $result;
	}
	
	public function fetch_assoc() {
		return $this->result->fetch(PDO::FETCH_ASSOC);
	}

}