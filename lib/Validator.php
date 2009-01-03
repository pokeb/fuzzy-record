<?php

class Validator {

	public static function validate_required($field_name,$value,&$message) {
		if ($value == "") {
			$message = static::human_readable_name($field_name)." is required";
			return false;
		}
		return true;
	}
	
	public static function validate_email_address($field_name,$value,&$message) {
		if (preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/",$value) == 0) {
			$message = static::human_readable_name($field_name)." is not a valid email address";
			return false;
		}
		return true;
	}
	
	public static function validate_password($field_name,$value,&$message) {
		if ((preg_match("/^.{6,12}$/",$value) == 0 || preg_match("/[0-9]/",$value) == 0)) {
			$message = static::human_readable_name($field_name)." must be between 6 and 12 characters, and must contain at least 1 number";
			return false;
		}
		return true;
	}
	
	public static function validate_true($field_name,$value,&$message) {
		if ($value !== true) {
			$message = static::human_readable_name($field_name)." must be true";
			return false;
		}
		return true;
	}

	public static function validate_false($field_name,$value,&$message) {
		if ($value !== false) {
			$message = static::human_readable_name($field_name)." must be false";
			return false;
		}
		return true;
	}
	
	public static function validate_min_length($field_name,$value,$min_length,&$message) {
		if (mb_strlen($value) < $min_length) {
			$message = static::human_readable_name($field_name)." must be at least $min_length characters long";
			return false;
		}
		return true;
	}

	public static function validate_max_length($field_name,$value,$max_length,&$message) {
		if (mb_strlen($value) > $max_length) {
			$message = static::human_readable_name($field_name)." must be no more than $max_length characters long";
			return false;
		}
		return true;
	}
	
	public static function validate_length($field_name,$value,$min_length,$max_length,&$message) {
		if (mb_strlen($value) < $min_length || mb_strlen($value) > $max_length) {
			$message = static::human_readable_name($field_name)." must be between $min_length and $max_length characters long";
			return false;
		}
		return true;
	}
	
	
	public static function validate_regular_expression($field_name,$value,$expression,&$message) {
		if (preg_match($expression,$value) == 0) {
			$message = "'$value' is not a valid value for ".static::human_readable_name($field_name);
			return false;
		}
		return true;
	}
	
	public static function validate_unique($field_name,$value,$instance,&$message) {
		$class_name = get_class($instance);
		$objects = $class_name::find(array($field_name => $value,"limit" => 1));

		if (count($objects) > 0 && !$objects[0]->is_the_same_as($instance)) {
			$message = "A ".static::human_readable_name($field_name)." with the value '".$value."' already exists";
			return false;		
		}
		return true;
	}
	
	protected static function human_readable_name($field_name) {
		return ucwords(str_replace("_"," ",$field_name));
	}


}