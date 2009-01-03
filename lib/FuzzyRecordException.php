<?php

class FuzzyRecordException extends Exception {

	public function __construct($message, $code=0) {
		FuzzyRecord::rollback();
		parent::__construct($message, $code);
	}
}