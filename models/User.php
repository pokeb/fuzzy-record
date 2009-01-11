<?php

/*
An example class demonstrating how you might model a user object
It demonstrates the has_many relationship type, see the UserLogin and Page models for examples of the reverse of this relationship
*/

class User extends FuzzyRecord {
	
	static protected $table = 'user';
	static protected $should_cache = false;
	
	static protected $properties = array(
		'id' => array('primary_key','auto_increment'),
		'email' => array('email_address','required','unique','searchable'),
		'password' => array('varchar','required'),
		'first_name' => array('min_length' => 2,'max_length' => 16,'searchable','like_searchable'),
		'last_name' => array('min_length' => 2,'max_length' => 16,'searchable','like_searchable'),
		'accepted_terms_and_conditions' => array('boolean','must_be_true','message' => 'Please confirm you have read and accept our terms and conditions'),
		'suspended' => array('boolean'),
		'type' => array('enum' => array('user','admin'), 'default' => 'user'),
		'description' => array('message' => 'Please enter a description of yourself','searchable'),
		'registration_date' => array('date_with_time','required')
	);
	
	static protected $relationships = array (
		"has_many" => array(
			"logins" => array("class" => "UserLogin", "dependent" => "delete"),
			"pages" => array("class" => "Page", "dependent" => "nullify")
		)
	);
}