<?php

/*
An example class demonstrating the use of file properties
All file properties must have 'save_path' specified - here we're using a constant defined in our config file
*/

class Document extends FuzzyRecord {

	static protected $table = 'document';

	static protected $properties = array(
		'id' => array('primary_key','auto_increment'),
		'author_id' => array('integer'),
		'file' => array('file','save_path' => DOCUMENT_SAVE_PATH),
		'last_modified' => array('date_with_time','order_by_default')
	);
	
	static protected $relationships = array(
		"belongs_to" => array(
			"author" => array("class" => "User")
		)
	);


}