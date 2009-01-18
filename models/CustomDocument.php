<?php

/*
An example class demonstrating the use of custom file names for uploaded files
In this example, files for the file property are saved with the id of this object as part of their name
*/

class CustomDocument extends FuzzyRecord {

	static protected $table = 'custom_document';

	static protected $properties = array(
		'id' => array('primary_key','auto_increment'),
		'author_id' => array('integer'),
		'file' => array('file','save_path' => DOCUMENT_SAVE_PATH),
		'last_modified' => array('date_with_time_and_timezone','order_by_default')
	);
	
	static protected $relationships = array(
		"belongs_to" => array(
			"author" => array("class" => "User")
		)
	);
	
	public function file_file_name() {
		return $this->id.".info";
	}


}