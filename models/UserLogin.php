<?php

/*
An example class demonstrating the belongs_to relationship
See also the User example class to see the reverse of this relationship
*/

class UserLogin extends FuzzyRecord {

	static protected $table = 'user_login';
	
	static protected $properties = array(
		'id' => array('primary_key','auto_increment'),
		'user_id' => array('integer','required'),
		'ip_address' => array('max_length' => 32,'required'),
		'date' => array('date_with_time')
	);
	
	static protected $relationships = array(
		"belongs_to" => array(
			"user" => array("class" => "User")
		)
	);

}