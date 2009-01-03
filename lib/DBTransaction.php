<?php

class DBTransaction {
	
	public $objects = array();
	public $is_implicit = false;
	
	public function rollback() {
		foreach ($this->objects as $object) {
			$object->remove_from_memory_cache();
		}
		$result = DB::query("rollback");
	}

	public function start() {
		$result = DB::query("start transaction");
	}	

	public function commit() {
		$result = DB::query("commit");
	}		
}