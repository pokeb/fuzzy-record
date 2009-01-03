<?php

class Section extends FuzzyRecord {

	static protected $table = 'section';

	static protected $properties = array(
		'id' => array('primary_key','auto_increment'),
		'url' => array('url','required'),
		'parent' => array('integer'),
		'name' => array('min_length' => 2,'max_length' => 64,'required'),
		'last_modified' => array('date_with_time')
	);
	
	static protected $relationships = array (
		"has_many" => array(
			"pages" => array("class" => "Page", "dependent" => "delete")
		)
	);

}