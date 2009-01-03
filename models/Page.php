<?php

class Page extends FuzzyRecord {
	
	static protected $table = 'page';

	static protected $properties = array(
		'id' => array('primary_key','auto_increment'),
		'url' => array('url','required'),
		'title' => array('min_length' => 2,'max_length' => 64,'required','searchable','like_searchable'),
		'body' => array('required','searchable'),
		'section_id' => array('integer'),
		'author_id' => array('integer'),
		'position' => array('sorter' => array('section')),
		'is_live' => array('boolean'),
		'last_modified' => array('date_with_time','order_by_default')
	);
	
	static protected $relationships = array(
		"belongs_to" => array(
			"section" => array("class" => "Section"),
			"author" => array("class" => "User")
		)
	);

}