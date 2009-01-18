<?php

class FuzzyRecord {
	
	// Name of the table this object stores its data in - should be defined in subclasses
	static protected $table;
	
	// Array of fields used in this object's table - should be defined in subclasses
	static protected $properties = array();
	
	// Array of relationships between this object and others - should be defined in subclasses
	static protected $relationships = array();
	
	// Can this object be written to the caches
	static protected $should_cache = true;

	// The original values of each primary key are stored here in case they change, so we can update records later
	protected $original_key_values = array();
	
	// Has this object been written to the database before?
	public $exists_in_database = false;
	
	// Populated with validation errors when validate() is called
	public $validation_errors = array();
	
	public $is_modified = false;
	//protected $dependent_objects = array();
	protected $objects_to_save_on_commit = array();
	protected $objects_to_delete_on_commit = array();
	
	protected $parent_objects_for_relations = array();

	public static function table_name() {
		return static::$table;
	}
	
	public static function properties() {
		return static::$properties;
	}

/*
CRUD Functions
*/

	public function __construct() {
	
		// Set default values
		foreach (static::$properties as $name => $info) {
			$this->$name = $this->default_value_for($name);
		}

		// Set primary key values if we got them as arguments
		$argument_count = func_num_args();
		if ($argument_count != 0) {
			if ($argument_count != count(static::primary_keys())) {
				throw new FuzzyRecordException("Not enough arguments to create a new ".static::$table." (required: ".implode(",",static::primary_keys()).")");
			}
			$keys = static::primary_keys();
			$arguments = func_get_args();
			for ($i=0; $i < $argument_count; $i++) {
				$key = $keys[$i];
				$this->$key = $arguments[$i];
			}
			

			// Attempt to read from the memory stores
			if (!$this->read_from_memory_cache()) {
				// Attempt to read from the database
				$sql = "select * from ".static::$table." where ".$this->where_for_keys();
				$statement = DB::prepare($sql,0,1);
	
				foreach (static::primary_keys() as $key) {
					$statement->bind_value(":$key",static::value_for_database($key,$this->$key));
				}
		
				$statement->execute();
	
				$values = $statement->fetch_assoc();
				if ($values) {
				
					foreach ($values as $key => $val) {
						$this->$key = static::value_from_database($key,$val);
					}
			
					$this->exists_in_database = true;
					$this->store_in_memory_cache();
				}
			}
		
		}

		// Store the values of our primary keys so we can use them for updates later on
		$this->set_primary_key_values();
	}
	
	// Save an existing object to the database
	public function write($cleanup_sorters=true) {
		DB::start_implicit_transaction();

		$class = get_class($this);
		$this->set_foreign_keys_from_parent_objects();	
		if (!$this->validate()) {

			return false;
		}
		

	
		$properties_to_write = "";
		foreach (static::$properties as $name => $info) {
			if (!in_array("file",$info)) {
				if ($properties_to_write != "") {
					$properties_to_write .= ", ";
				}
				$properties_to_write .= DB::$db_quote_mark.$name.DB::$db_quote_mark." = :$name";
			}

		}
		$sql = "update ".static::$table." set $properties_to_write where ".$this->where_for_original_keys();

		$statement = DB::prepare($sql);

		$properties_sql = "";
		foreach (static::$properties as $name => $info) {
			if (!in_array("file",$info)) {
				if ($properties_sql != "") {
					$properties_sql .= ", ";
				}
				$properties_sql .= DB::$db_quote_mark.$name.DB::$db_quote_mark." = :$name";
			}
		}
		
		foreach (static::$properties as $name => $info) {
			if (!in_array("file",$info)) {
				$statement->bind_value(":$name",static::value_for_database($name,$this->$name));
			}
		}
		foreach ($this->original_key_values as $name => $value) {
			$statement->bind_value(":_original_$name",static::value_for_database($name,$this->$name));
		}

		$statement->execute();


			
		$this->remove_from_memory_cache();
			
		if ($cleanup_sorters) {
			$this->correct_sorter_positions();
		}
		
		$primary_keys_updated = false;
		foreach ($this->original_key_values as $key => $val) {
			if ($this->$key != $val) {
				$primary_keys_updated = true;
				break;
			}
		}

		if ($primary_keys_updated) {

			//Update relationships
			foreach (static::$relationships as $type => $relations) {
			
				switch ($type) {
					case "has_many":
						foreach ($relations as $name => $info) {
							$dependents = $this->objects_for_has_many_relation($name);
							foreach ($dependents as $obj) {
								foreach (static::primary_keys() as $key) {
									$key_name = static::$table."_".$key;
									$obj->$key_name = $this->$key;
								}
								$obj->save();
							}
							
						}
						break;
				}
			}
			
		}
		$this->write_files();
		$this->commit_dependent_objects();
		$this->set_primary_key_values();
		$this->store_in_memory_cache();

		DB::commit_implicit_transaction();
		
		return true;
	}	

	// Save a new object to the database
	public function write_new() {

		DB::start_implicit_transaction();
		$this->set_foreign_keys_from_parent_objects();
		if (!$this->validate()) {
			return false;
		}
		

		$this->reset_sorters();
	
		//Cache this stuff somewhere?
		$properties_sql = "";
		$values_sql = "";
		foreach (static::$properties as $name => $info) {
			if (!in_array("auto_increment",$info) && !in_array("file",$info)) {
				if ($properties_sql != "") {
					$properties_sql .= ",";
					$values_sql .= ",";
				}
				$properties_sql .= DB::$db_quote_mark.$name.DB::$db_quote_mark;
				$values_sql .= ":$name";
			}
		}
		$sql = "insert into ".static::$table." ($properties_sql) values ($values_sql)";
		$statement = DB::prepare($sql);
		
	
		foreach (static::$properties as $name => $info) {
			if (!in_array("auto_increment",$info) && !in_array("file",$info)) {
				$statement->bind_value(":$name",static::value_for_database($name,$this->$name));
			}
		}

		$statement->execute();

		$this->correct_sorter_positions();
		
		foreach (static::primary_keys() as $key) {
			if (in_array("auto_increment",static::$properties[$key])) {
				$this->$key = DB::last_insert_id(static::$table."_".$key."_seq");
			}
		}
		
		$this->write_files();
		$this->commit_dependent_objects();			
		$this->set_primary_key_values();
		$this->store_in_memory_cache();
		$this->exists_in_database = true;
		
		DB::commit_implicit_transaction();
		return true;
	}
	
	protected function delete_files() {
		foreach (static::$properties as $name => $info) {
			if (in_array("file",$info)) {
				$file = $this->$name;
				// See if file has been set
				if ((!is_object($file) && $file == "")) {
					continue;
				}
				if (!is_object($file) || !is_a($file,"File")) {
					throw new FuzzyRecordException("The value of $name is not a File");
				}
				$file->delete();
			}
		}
	}
	
	protected function write_files() {
	
	
		foreach (static::$properties as $name => $info) {
			if (in_array("file",$info)) {

				if (!isset($this->$name)) {
					continue;
				}
				$file = $this->$name;
				
				// See if file has been set
				if ((!is_object($file) && $file == "")) {
					continue;
				}
				if (!is_object($file) || !is_a($file,"File")) {
					throw new FuzzyRecordException("The value of $name is not a File");
				}
				
				$class = get_class($this);
					
				if (!array_key_exists("save_path",$info)) {
					throw new FuzzyRecordException("$class does not define a save_path for the File property $name");
				}
				$save_directory = $info['save_path'];
				
				$reflection_obj = new ReflectionClass($class); 
				$method = $name."_file_name";
				if ($reflection_obj->hasMethod($method)) {
					$file_name = $this->$method();
				} else {
					$file_name = $file->name();
				}
				$file->write($save_directory."/".$file_name);
				
				$sql = "update ".static::$table." set ".DB::$db_quote_mark.$name.DB::$db_quote_mark."= :file_name where ".$this->where_for_original_keys();
				$statement = DB::prepare($sql);
				foreach ($this->original_key_values as $key_name => $value) {
					$statement->bind_value(":_original_$key_name",static::value_for_database($key_name,$this->$key_name));
				}
				$statement->bind_value(":file_name",$file_name);
				$statement->execute();
			}
		}	
	}
	
	// Save an object to the database in the appropriate manner depending on whether it has been written previously or not
	public function save() {
		if (!$this->exists_in_database) {
			return $this->write_new();
		} else {
			return $this->write();
		}

	}
	
	
	public function delete() {
	
		DB::start_implicit_transaction();
	
		$sql = "delete from ".static::$table." where ".$this->where_for_keys();
		$statement = DB::prepare($sql);

		
		foreach (static::primary_keys() as $key) {
			$statement->bind_value(":$key",$this->$key);
		}
		
		$statement->execute();
		
		$class = get_class($this);
		
		//Delete objects 
		foreach (static::$relationships as $type => $relations) {
		
			switch ($type) {
				case "has_many":
				
					foreach ($relations as $name => $info) {
					
						$dependents = $this->objects_for_has_many_relation($name);
						foreach ($dependents as $obj) {
							
							// If this object's class declares a belongs_to relationship, we should delete it
							$child_class = get_class($obj);
							$belongs_to = false;
							$cascade_delete = false;
							foreach ($child_class::$relationships as $child_type => $child_relations) {
								switch ($child_type) {
									case "belongs_to":
										foreach ($child_relations as $rel_name => $rel_info) {
											if ($rel_info['class'] == $class) {
												$belongs_to = true;
												$cascade_delete = (array_key_exists("dependent",$info) && $info['dependent'] == "delete");
												break;
											}
										}
										break;
								}
								if ($belongs_to) {
									break;
								}
							}
							if ($belongs_to) {
								if ($cascade_delete) {
									$obj->delete();
								} else {
									$obj->nullify_relations_for_object($this);
								}
							}
						}
						
					}
					break;
			}
		}
		$this->remove_from_memory_cache();
		DB::commit_implicit_transaction();
		$this->exists_in_database = false;
	}
	
	protected function commit_dependent_objects() {
		foreach ($this->objects_to_save_on_commit as $object) {
			$object->save();
		}
		foreach ($this->objects_to_delete_on_commit as $object) {
			$object->delete();
		}
	}
	
	protected function set_foreign_keys_from_parent_objects() {
		foreach ($this->parent_objects_for_relations as $relationship => $object) {	
			$parent_object_class = get_class($object);
			foreach ($parent_object_class::primary_keys() as $key) {
				$foreign_key = $relationship."_".$key;
				$this->$foreign_key = $object->$key;
			}
		}
	}
	
	public function validate() {
		$this->validation_errors = array();
		foreach (static::$properties as $field => $info) {
			$message = "";
			if ((in_array("primary_key",$info) && !in_array("auto_increment",$info)) || in_array("required",$info)) {
				if (!Validator::validate_required($field,$this->$field,$message)) {
					$this->validation_errors[$field] = $this->validation_message_for_field($field,"required",$message);
				}
			}
			if (in_array("email_address",$info)) {
				if (!Validator::validate_email_address($field,$this->$field,$message)) {
					$this->validation_errors[$field] = $this->validation_message_for_field($field,"email_address",$message);
				}
			}
			if (in_array("password",$info)) {
				if (!Validator::validate_password($field,$this->$field,$message)) {
					$this->validation_errors[$field] = $this->validation_message_for_field($field,"password",$message);
				}
			}
			if (in_array("boolean",$info) && in_array("must_be_true",$info)) {
				if (!Validator::validate_true($field,$this->$field,$message)) {
					$this->validation_errors[$field] = $this->validation_message_for_field($field,"must_be_true",$message);
				}
			}
			if (in_array("boolean",$info) && in_array("must_be_false",$info)) {
				if (!Validator::validate_false($field,$this->$field,$message)) {
					$this->validation_errors[$field] = $this->validation_message_for_field($field,"must_be_false",$message);
				}
			}
			if (array_key_exists("min_length",$info) && array_key_exists("max_length",$info)) {
				if (!Validator::validate_length($field,$this->$field,$info['min_length'],$info['max_length'],$message)) {
					$this->validation_errors[$field] = $this->validation_message_for_field($field,"length",$message);
				}
			}
			if (array_key_exists("min_length",$info)) {
				if (!Validator::validate_min_length($field,$this->$field,$info['min_length'],$message)) {
					$this->validation_errors[$field] = $this->validation_message_for_field($field,"min_length",$message);
				}
			}
			if (array_key_exists("max_length",$info)) {
				if (!Validator::validate_max_length($field,$this->$field,$info['max_length'],$message)) {
					$this->validation_errors[$field] = $this->validation_message_for_field($field,"max_length",$message);
				}
			}
			if (in_array("unique",$info)) {
				$where = "";
				if ($this->exists_in_database) {
					$where = " and !(".$this->where_for_keys().")";
				}
				if (!Validator::validate_unique($field,$this->$field,$this,$message)) {
					$this->validation_errors[$field] = $this->validation_message_for_field($field,"unique",$message);
				}
			}
		}
		return (count($this->validation_errors) == 0);
	}
	
	protected function validation_message_for_field($field,$failure_type,$message) {
		if (!key_exists($field,static::$properties)) {
			throw new FuzzyRecordException("Property '$field' not found");
		}

		$property = static::$properties[$field];
		if (key_exists($failure_type."_message",$property)) {
			return $property[$failure_type."_message"];
		} elseif (key_exists("message",$property)) {
			return $property['message'];
		} else {
			return $message;
		}
	}
	
	static protected function value_from_database($field,$value) {
		switch (static::field_type($field)) {
			case "file":
				$file_properties = static::$properties[$field];
				if (!array_key_exists("save_path",$file_properties)) {
					$class = get_called_class();
					throw new FuzzyRecordException("$class does not define a save_path for the File property $field");
				}
				if ($value == "") {
					return NULL;
				}
				return new File($file_properties['save_path']."/".$value);
				
			case "date_with_time":
			case "date_with_time_and_timezone":
				return new Date($value);
				
			case "boolean":
				
				if (empty($value) || $value === "0" || $value === "f") {
					return false;			
				}
				return true;
			default:
				return $value;
		}
	}
	
	static protected function value_for_database($field,$value) {
		switch (static::field_type($field)) {
			case "date_with_time":
				if (!$value) {
					return NULL;
				} elseif (!is_object($value) || get_class($value) != "Date") {
					throw new FuzzyRecordException("The value of '$field' must be a date object");
				}
				return $value->db_date_with_time();
			case "date_with_time_and_timezone":
				if (!$value) {
					return NULL;
				}
				return $value->db_date_with_time_and_timezone();
			case "boolean":
				if ($value === false) {
					if (DB_TYPE == "postgresql") {
						return "f";
					} else {
						return "0";
					}
				} else {
					if (DB_TYPE == "postgresql") {
						return "t";
					} else {
						return "1";
					}				
				}
			default:
				return $value;
		}
	}
	
	static public function default_value_for($field) {
		if (!key_exists($field,static::$properties)) {
			throw new FuzzyRecordException("Property '$field' not found");
		}
		$property = static::$properties[$field];
		if (key_exists("default", $property)) { 
			return $property['default'];
		}
		switch (static::field_type($field)) {
			case "boolean":
				return false;
			default:
				return NULL;
		}
	}
	
	static public function field_type($field) {
		if (!key_exists($field,static::$properties)) {
			throw new FuzzyRecordException("Property '$field' not found");
		}
		$options = static::$properties[$field];
		$field_types = array("file","integer","sorter","boolean","date_with_time","date_with_time_and_timezone","date","time","email_address","password","varchar","text","enum");
		foreach ($field_types as $type) {
			if (in_array($type, $options) || key_exists($type,$options)) {
				return $type;
			}
		}
		if (in_array("auto_increment",$options)) {
			return "integer";
		} elseif (key_exists("max_length", $options) && $options['max_length'] < 256) {
			return "varchar";
		} else {
			return "text";
		}
	}

	protected function where_for_keys() {
		$keys_sql = "";
		$i=0;
		foreach (static::primary_keys() as $key) {
			$keys_sql .= DB::$db_quote_mark.$key.DB::$db_quote_mark." = :$key";
			$i++;
			if ($i<count(static::primary_keys())) {
				$keys_sql .= " and ";
			}
		}
		return $keys_sql;
	}

	protected function where_for_original_keys() {
		$keys_sql = "";
		$i=0;
		foreach (static::primary_keys() as $key) {
			$keys_sql .= DB::$db_quote_mark.$key.DB::$db_quote_mark." = :_original_$key";
			$i++;
			if ($i<count(static::primary_keys())) {
				$keys_sql .= " and ";
			}
		}
		return $keys_sql;
	}
	
	protected function set_primary_key_values() {
		foreach (static::$properties as $name => $info) {
			if (in_array("primary_key", $info)) {
				$this->original_key_values[$name] = $this->$name;
			}
		}
	}



	

	
/*
Functions for finding instances
*/

	static private function build_find_args($fields,$arguments,$function_name) {

		$called_class = get_called_class();
		
		// Did we get an extra parameter
		if (count($fields)+1 == count($arguments) && is_array($arguments[count($arguments)-1])) {
			$args = $arguments[count($arguments)-1];
			array_splice($arguments,count($arguments)-1);
		} else {
			$args = array();
		}
		
		if (count($fields) != count($arguments)) {
			throw new FuzzyRecordException("$called_class::$function_name : Incorrect number of arguments");
		}
		
		$i=0;
		foreach ($fields as $called_field_name) {
			
			$field = $called_field_name;
			
			$operators = array("not","greater_than","less_than","not_like","like");
			foreach ($operators as $operator) {
				if (mb_substr($field,0-mb_strlen($operator)) == $operator) {
					$field = mb_substr($field,0,mb_strlen($field)-(mb_strlen($operator)+1));
				}
			}
		
			if (!array_key_exists($field,static::$properties)) {
				$found_relationship = false;
				foreach (static::$relationships as $type => $relations) {
					switch ($type) {
						case "has_many":
							foreach ($relations as $relationship_name => $info) {
								if ($relationship_name != $field."s") {
									continue;
								}
			
								$object = $arguments[$i];
								if (!is_object($object)) {
									throw new FuzzyRecordException("$called_class::$function_name : Invalid argument given - expected an object");
								}
								
								$child_class = get_class($object);
								if (!isset($child_class::$relationships['belongs_to'])) {
									throw new FuzzyRecordException("No belongs to relationship exists in class '$child_class' to connect it to '".get_class($this)."'");
								}
								
								$found_suitable_belongs_to_relationship = false;
								foreach ($child_class::$relationships['belongs_to'] as $child_relationship_name => $child_info) {
									if (!array_key_exists("class", $child_info)) {
										throw new FuzzyRecordException("Improperly defined class '$child_class': all belongs to relationships must define a parent class");
									}
									if ($child_info['class'] == $called_class) {	
										foreach (static::primary_keys() as $key) {
											$foreign_key = $child_relationship_name."_".$key;
											$args[$key] = $object->$foreign_key;
										}
										$found_suitable_belongs_to_relationship = true;
										
										break;
									}
								}
								if ($found_suitable_belongs_to_relationship) {
									$found_relationship = true;
								}
							}
							break;
						case "belongs_to":
							foreach ($relations as $relationship_name => $info) {
							
								if ($relationship_name != $field) {
									continue;
								}
							
								$object = $arguments[$i];
								if (!is_object($object)) {
									throw new FuzzyRecordException("$called_class::$function_name : Invalid argument given - expected an object");
								}
								$class = get_class($object);
								foreach ($class::primary_keys() as $key) {
									$foreign_key = $relationship_name."_".$key;
									$args[$foreign_key] = $object->$key;
								}
								$found_relationship = true;
							}
							break;
							
					}
					if ($found_relationship) {
						break;
					}
				}
				if (!$found_relationship) {
					throw new FuzzyRecordException("$called_class::$function_name : $called_class does not have a '$field' property");
				}
			} else {
				$args[$called_field_name] = $arguments[$i];
			}
			$i++;
		}
		return $args;
	}
	
	static public function __callStatic($name, $arguments) {

		$class = get_called_class();
		
		if ($name == "find_all") {
			return static::find();
			
		} elseif (mb_substr($name,0,9) == "count_by_") {
			$fields = mb_substr($name,9,mb_strlen($name)-9);
			$fields = explode("_and_",$fields);
			return static::count(static::build_find_args($fields,$arguments,$name));	
			
		} elseif (mb_substr($name,0,12) == "find_all_by_") {
			$fields = mb_substr($name,12,mb_strlen($name)-12);
			$fields = explode("_and_",$fields);
	
			$args = array();
	
			// Did we get limit / start_from as number arguments
			if (count($arguments) == count($fields)+1 && is_numeric($arguments[count($arguments)-1])) {
				$args['limit'] = $arguments[count($fields)];
				array_splice($arguments,1,1);
			} elseif (count($arguments) == count($fields)+2 && is_numeric($arguments[count($arguments)-2]) && is_numeric($arguments[count($arguments)-1])) {
				$args['limit'] = $arguments[count($fields)+1];
				$args['start_from'] = $arguments[count($fields)];
				array_splice($arguments,1,2);
			}
			$args = array_merge($args,static::build_find_args($fields,$arguments,$name));
						
			
			return static::find($args);
			
		} elseif (mb_substr($name,0,8) == "find_by_") {
			$fields = mb_substr($name,8,mb_strlen($name)-8);
			$fields = explode("_and_",$fields);
			$args = static::build_find_args($fields,$arguments,$name);
			$args['limit'] = 1;

			$results = static::find($args);
			if (count($results) > 0) {
				return $results[0];
			}
			
			return NULL;
			
		} elseif ($name == "count_where") {
			$args = array();
			if (count($arguments) == 0) {
				throw new FuzzyRecordException("$class::$name : Incorrect number of arguments");
			}
			$args['where'] = $arguments[0];
			return static::count($args);		
			
		} elseif ($name == "find_all_where") {

			$args = array();
			if (count($arguments) == 0) {
				throw new FuzzyRecordException("$class::$name : Incorrect number of arguments");
			}
			$args['where'] = $arguments[0];

			// Did we get limit / start_from as number arguments
			if (count($arguments) == 2 && is_numeric($arguments[1])) {
				$args['limit'] = $arguments[1];
				array_splice($arguments,1,1);
				
			} elseif (count($arguments) == 3  && is_numeric($arguments[1]) && is_numeric($arguments[2])) {
				$args['start_from'] = $arguments[1];
				$args['limit'] = $arguments[2];	
				array_splice($arguments,1,2);
			}
			
			// Remove where argument
			array_splice($arguments,0,1);
			
			$fields = array();
			$args = array_merge($args,static::build_find_args($fields,$arguments,$name));
			return static::find($args);
			
		} elseif ($name == "find_where") {
			if (count($arguments) == 0) {
				throw new FuzzyRecordException("$class::$name : Incorrect number of arguments");
			}
			$args = array('where' => $arguments[0], 'limit' => 1);
			$fields = array();
			$args = array_merge($args,static::build_find_args($fields,$arguments,$name));
			$results = static::find($args);
			if (count($results) > 0) {
				return $results[0];
			}
			return NULL;
		}
	}
	
	
	public function __call($name, $arguments) {

		foreach (static::$relationships as $type => $relations) {
			switch ($type) {
				case "has_many":
					foreach ($relations as $relationship_name => $info) {
						
						if ($relationship_name != $name) {
							continue;
						}
						
						$args = NULL;
						if (count($arguments) > 0) {
							$args = $arguments[0];
						}
						return $this->objects_for_has_many_relation($name,$args);
					}
					break;
				case "belongs_to":
					foreach ($relations as $relationship_name => $object) {
						
						if ($relationship_name != $name) {
							continue;
						}
						return $this->parent_object_for_belongs_to_relation($name);
					}
					break;
					
			}
		}
	}
	
	public function is_the_same_as($object) {
		$class = get_class($object);
		if ($class::$table != static::$table) {
			return false;
		}
		foreach (static::primary_keys() as $key) {
			if ($this->$key != $object->$key) {
				return false;
			}
		}
		return true;
	}

	public function __set($name, $value) {
		foreach (static::$relationships as $type => $relations) {
			switch ($type) {
				case "has_many":
					
					foreach ($relations as $relationship_name => $info) {
						
						if ($relationship_name != $name) {
							continue;
						}
						if (!is_array($value) && isset($value)) {
							throw new FuzzyRecordException("When setting ".static::$table." $name, value must be an array");
						}
						$existing_objects = $this->$name;						
						if (!isset($value)) {
							foreach ($existing_objects as $existing_object) {
								$this->objects_to_delete_on_commit[] = $existing_object;
							}
						} else {
							$new_objects = $value;
			
							foreach ($new_objects as $new_object) {
								$child_class = get_class($new_object);
								if ($child_class != $info['class']) {
									print_r($info);
									print_r($this);
									throw new FuzzyRecordException("Attempted to add an object of the wrong type for ".get_class($this)."->$name (got: ".$child_class.", expected: ".$info['class'].")");
								}
								if (!isset($child_class::$relationships['belongs_to'])) {
									throw new FuzzyRecordException("No belongs to relationship exists in class '$child_class' to connect it to '".get_class($this)."'");
								}
								$found_suitable_belongs_to_relationship = false;
								foreach ($child_class::$relationships['belongs_to'] as $child_relationship_name => $child_info) {
									if (!array_key_exists("class", $child_info)) {
										throw new FuzzyRecordException("Improperly defined class '$child_class': all belongs to relationships must define a parent class");
									}
									if ($child_info['class'] == get_class($this)) {
										
										$new_object->parent_objects_for_relations[$child_relationship_name] = $this;
										$new_object->is_modified = true;
										$found_suitable_belongs_to_relationship = true;
										
										break;
									}
								}
								if (!$found_suitable_belongs_to_relationship) {
									throw new FuzzyRecordException("No belongs to relationship exists in class '$child_class' to connect it to '".get_class($this)."'");							
								}

								$this->objects_to_save_on_commit[] = $new_object;
							}
							$new_dependent_objects = array();

							$i=0;
							foreach ($existing_objects as $existing_object) {
								$object_stays_in_relationship = false;
								foreach ($new_objects as $new_object) {
									if ($new_object->is_the_same_as($existing_object)) {
										$object_stays_in_relationship = true;
										break;
									}
								}
								if (!$object_stays_in_relationship) {
									$this->objects_to_delete_on_commit[] = $existing_object;
								}
							}
						}
						//$this->dependent_objects[$name] = $new_objects;
						return;
					}
					break;
				case "belongs_to":
				
					foreach ($relations as $relationship_name => $info) {
					
						if ($relationship_name != $name) {
							continue;
						}
						if (isset($value)) {
							$parent_object = $value;
							$parent_class = get_class($parent_object);
							if ($parent_class != $info['class']) {
								throw new FuzzyRecordException("Attempted to set of the wrong type for ".static::$table." $name (got: ".$parent_class::$table.", expected: $object");
							}
						}
						if (isset($parent_object)) {
							$this->parent_objects_for_relations[$relationship_name] = $parent_object;
						} else {
							if (!array_key_exists("class", $info)) {
								throw new FuzzyRecordException("Improperly defined class '".get_class($this)."': all belongs to relationships must define a parent class");
							}
							$parent_class = $info["class"];
							foreach ($parent_class::primary_keys() as $key) {
								$foreign_key = $relationship_name."_".$key;
								$this->$foreign_key = NULL;
							}
							
						}
						return;
					}
					
			}
		}
		if (array_key_exists($name, static::$properties)) {
			$this->is_modified = true;
		}
		$this->$name = $value;
	}

	public function __isset($name) {
		foreach (static::$relationships as $type => $relations) {
			foreach ($relations as $relationship_name => $info) {
				if ($relationship_name == $name) {
					return true;
				}
			}
		}
	}

	
	public function __get($name) {


	
		foreach (static::$relationships as $type => $relations) {
			switch ($type) {
				case "has_many":
					foreach ($relations as $relationship_name => $info) {
						
						if ($relationship_name != $name) {
							continue;
						}
						//if (array_key_exists ($name,$this->dependent_objects)) {
						//	return $this->dependent_objects[$name];
						//}
						$objects = $this->objects_for_has_many_relation($name);
						//$this->dependent_objects[$name] = $objects;
						return $objects;
					}
					break;
				case "belongs_to":
					foreach ($relations as $relationship_name => $object) {
						
						if ($relationship_name != $name) {
							continue;
						}
						return $this->parent_object_for_belongs_to_relation($name);
					}
					break;
					
			}
		}
		//return $this->$name;
	}
	
	static public function delete_all() {
		$objects = static::find_all();
		foreach ($objects as $object) {
			$object->delete();
		}
	}
	
	protected function parent_object_for_belongs_to_relation($name) {

		$class = get_class($this);
		
		if (isset($this->parent_objects_for_relations[$name])) {
			return $this->parent_objects_for_relations[$name];
		}
	
		if (!array_key_exists("belongs_to",static::$relationships)) {
			throw new FuzzyRecordException("No belongs_to relations exists for ".static::$table);		
		}
		$relation = static::$relationships["belongs_to"];
		if (!array_key_exists($name,$relation)) {
			throw new FuzzyRecordException("No belongs_to relation named $name exists for ".get_class($this));		
		}
		$parent_class = $relation[$name]['class'];
		$obj = new $parent_class();
		$keys = array();
		foreach ($parent_class::primary_keys() as $key) {
			$key_name = $name."_".$key;
			if (!array_key_exists($key_name,static::$properties)) {
				throw new FuzzyRecordException("Class $class is missing a belongs_to relationship for $parent_class");
			}
			$keys[] = $this->$key_name;
		}
		$reflection_obj = new ReflectionClass($parent_class); 
		return $reflection_obj->newInstanceArgs($keys);
		
	}
	
	protected function objects_for_has_many_relation($name,$args=array()) {

		$class = get_class($this);

		if (!array_key_exists("has_many",static::$relationships)) {
			throw new FuzzyRecordException("No has_many relations exists for ".static::$table);		
		}
		$relation = static::$relationships["has_many"];
		if (!array_key_exists($name,$relation)) {
			throw new FuzzyRecordException("No has_many relation named $name exists for $class");		
		}
		$info = $relation[$name];
		$child_class = $info['class'];
		$obj = new $child_class();
		
		foreach ($child_class::$relationships as $type => $relations) {
			switch ($type) {
				case "belongs_to":
					
					foreach ($relations as $relationship_name => $info) {
						if ($info['class'] == $class) {
						
							foreach (static::primary_keys() as $key) {
								$foreign_key = $relationship_name."_".$key;
								if (!array_key_exists($foreign_key,$child_class::$properties)) {
									throw new FuzzyRecordException("Class $child_class is missing a belongs_to relationship for $class");
								}
								
								$args[$foreign_key] = $this->original_key_values[$key];
							}
						}
					}
					break;
			}
		}
		return $obj->find($args);
	}
	
	protected function nullify_relations_for_object($object) {

		$modified = false;
		$class = get_class($object);
		foreach (static::$relationships as $type => $relations) {
			foreach ($relations as $relationship_name => $info) {	
				if ($info['class'] == $class) {
					$this->$relationship_name = NULL;
					$modified = true;
				}
			}
		}
		if ($modified) {
			$this->write(false);
		}
	}
	
	static protected function parse_find_arguments($arguments, &$order_by, &$order_direction, &$relevance, &$from, &$where, &$start_from, &$limit) {
		$where_clauses = array();
		foreach ($arguments as $key => $value) {
			
			switch ($key) {
				case "start_from":
					$start_from = $value;
					break;
				case "limit":
					$limit = $value;
					break;				
				case "order_by":
					$order_by = "order by ".DB::$db_quote_mark.$value.DB::$db_quote_mark;
					break;
				case "sort":
					if ($value == "descending") {
						$order_direction = "desc";
					} else {
						$order_direction = "asc";
					}
					break;
				case "where":
					$where_clauses[] = $arguments['where'];
					break;
				case "not_like":
				case "like":
					$not_operator = "";
					if ($key == "not_like") {
						$not_operator = " not";
					}
				
					if (count(static::like_search_fields()) == 0) {
						throw new FuzzyRecordException("'".static::$table."' does not have any fields defined as like_searchable");
					}
					$match_by = "";
					foreach (static::like_search_fields() as $field) {
						if ($match_by != "") {
							$match_by .= " or ";
						}
						$match_by .= DB::$db_quote_mark.$field.DB::$db_quote_mark." $not_operator".DB::$db_ilike_operator." ".DB::escape($value);
					}
					$where_clauses[] = $match_by;
					break;
				case "match":
					if (count(static::$search_fields) == 0) {
						throw new FuzzyRecordException("'".static::$table."' does not have any fields defined as searchable");
					}
					if (DB_TYPE == "mysql") {
						$having = "having relevance > 0";
						$relevance  = ", match (".DB::$db_quote_mark.implode(DB::$db_quote_mark.",".DB::$db_quote_mark,static::$search_fields).DB::$db_quote_mark.") against ('".DB::escape($value)."') as relevance";		
					} else {
						$pg_keyword_list = implode(" & ",explode(" ",$value));
						$relevance = ", ts_rank_cd(".DB::escape($this->ft_search_index).", query) as relevance";
						$from .= ", to_tsquery('english', '".DB::escape($pg_keyword_list)."') query";
						$where .= "query @@ ".DB::escape($this->ft_search_index);
					}
					if ($order_by == "") {
						$order_by = "order by relevance";
						$order_direction = "desc";
					}
					$where_clauses[] = $match_by;
					break;
				default:
					$operator = "=";
					$field = $key;
					if (mb_substr($field,-4) == "_not") {
						$field = mb_substr($field,0,mb_strlen($field)-4);
						$operator = "!=";
					} elseif (mb_substr($field,-13) == "_greater_than") {
						$field = mb_substr($field,0,mb_strlen($field)-13);
						$operator = ">";
					} elseif (mb_substr($field,-10) == "_less_than") {
						$field = mb_substr($field,0,mb_strlen($field)-10);
						$operator = "<";
					} elseif (mb_substr($field,-9) == "_not_like") {
						$field = mb_substr($field,0,mb_strlen($field)-9);
						$operator = "not ".DB::$db_ilike_operator;
					} elseif (mb_substr($field,-5) == "_like") {
						$field = mb_substr($field,0,mb_strlen($field)-5);
						$operator = DB::$db_ilike_operator;
					}
					if (array_key_exists($field,static::$properties)) {
						$where_clauses[] = DB::$db_quote_mark.$field.DB::$db_quote_mark." $operator ".DB::escape($value);
						
					}
					break;
			}
			if (count($where_clauses) > 0) {
				$where = " where ".implode(" and ",$where_clauses);
			}

		}

	}
	
	static public function count($arguments=array()) {
		$order_by = "";
		$order_direction = "";
		$having = "";
		$relevance = "";
		$from = "";
		$where = "";
		$start_from = -1;
		$limit = -1;
		
		static::parse_find_arguments($arguments,$order_by,$order_direction,$relevance,$from,$where,$start_from,$limit);

		$sql = "select count(*) as count from ".DB::$db_quote_mark.static::$table.DB::$db_quote_mark." $from $where";
		$statement = DB::prepare($sql);
		$statement->execute();

		$count = $statement->fetch_assoc();
		return $count['count'];
	}
	
	static public function find($arguments=array()) {
	

	
		$order_by = "";
		$order_direction = "";
		$having = "";
		$relevance = "";
		$from = "";
		$where = "";
		$start_from = -1;
		$limit = -1;
		
		static::parse_find_arguments($arguments,$order_by,$order_direction,$relevance,$from,$where,$start_from,$limit);

		if ($order_by == "") {
			foreach (static::$properties as $name => $info) {
				if (in_array("order_by_default", $info)) {
					$order_by = "order by ".DB::$db_quote_mark.$name.DB::$db_quote_mark;
					$order_direction = "asc";
					break;
				} elseif (in_array("order_by_default_descending", $info)) {
					$order_by = "order by ".DB::$db_quote_mark.$name.DB::$db_quote_mark;
					$order_direction = "desc";
					break;
				}
			}
		}

		$sql = "select ".static::select_keys_sql()." $relevance from ".DB::$db_quote_mark.static::$table.DB::$db_quote_mark." $from $where $having $order_by $order_direction";
		$statement = DB::prepare($sql,$start_from,$limit);

		$statement->execute();

		$matches = array();
		while ($item = $statement->fetch_assoc()) {
			$keys = array();
			foreach (static::primary_keys() as $key) {
				$keys[] = $item[$key];
			}
			$reflection_obj = new ReflectionClass(get_called_class()); 
			$matches[] = $reflection_obj->newInstanceArgs($keys);
		}
		return $matches;
	}

/*
Sorter functions
*/
	
	public function move_up($sorter) {
		
		$class = get_class($this);
		if ($this->$sorter > 0) {
			$args = $this->sort_arguments($sorter);
			$args[$sorter] = $this->$sorter-1;
			$objects = $class::find($args);
			if (count($objects) > 0) {
				$object = $objects[0];
				$object->position = $this->position;
				$object->write(false);
			}
			$this->position--;
			$this->save();
		}
		$this->correct_positions($sorter);
	}
	
	public function max_position_for_sorter($sorter) {
		$class = get_class($this);
		$args = $this->sort_arguments($sorter);
		
		$args['limit'] = 1;
		$args['order_by'] = $sorter;
		$args['sort'] = "descending";
		$objects = $class::find($args);
		$max = 0;
		if (count($objects) > 0) {
			$object = $objects[0];
			$max = $object->$sorter;
		}
		return $max;
	
	}
	
	public function move_down($sorter) {
		$class = get_class($this);
		$args = $this->sort_arguments($sorter);
		$max = $this->max_position_for_sorter($sorter);
		if ($this->$sorter < $max) {
			$args[$sorter] = $this->$sorter+1;
			$objects = $class::find($args);

			if (count($objects) > 0) {
				$object = $objects[0];
				$object->position = $this->position;
				$object->write(false);
			}		
			$this->position++;
			$this->save();
		}
		$this->correct_positions($sorter);
	}		
		
	protected function correct_positions($sorter) {
		$class = get_class($this);
		$args = $this->sort_arguments($sorter);

		$args['order_by'] = $sorter;	
		$pages = Page::find($args);
		$sort_position = 1;
		foreach ($pages as $page) {
			$page->$sorter = $sort_position;
			$page->write(false);
			$sort_position++;
		}
	}
	
	protected function reset_sorters() {
		foreach (static::sorters() as $sorter) {
			$this->$sorter = 0;
		}
	}
	
	protected function correct_sorter_positions() {
		foreach (static::sorters() as $sorter) {
			$this->correct_positions($sorter);
		}	
	}
	
	protected function sort_arguments($sorter) {
		$class = get_class($this);
		if (!array_key_exists($sorter,static::$properties)) {
			throw new FuzzyRecordException("Cannot sort $class : no '$sorter' property exists");
		}
		$attributes = static::$properties[$sorter];
		if (!array_key_exists("sorter",$attributes) && !in_array("sorter",$attributes)) {
			throw new FuzzyRecordException("Cannot sort $class : '$sorter' property is not defined as a sorter");
		}
		$args = array();
		if (array_key_exists("sorter",$attributes)) {
			foreach($attributes['sorter'] as $sort_constraint) {
				$args[$sort_constraint] = $this->$sort_constraint;
			}
		}
		return $args;
	}
	
/*
Memcache functions
*/

	public function identifier() {
		$identifier = static::$table."-";
		foreach (static::primary_keys() as $key) {
			$identifier .= $key."=".$this->$key.":";
		}
		return $identifier;
	}

	public function read_from_memory_cache() {
		if (!$this->should_cache) return false;
		$cached_object = MemoryStore::read($this->identifier());
		if ($cached_object) {
			foreach (get_object_vars($cached_object) as $key => $val) {
				$this->$key = $val;
			}
			$this->exists_in_database = true;
			return true;
		}
		return false;
	}
	
	public function store_in_memory_cache() {
		if (!$this->should_cache) return false;
		return MemoryStore::write($this->identifier(),$this);
	}
	
	public function remove_from_memory_cache() {
		if (!$this->should_cache) return false;
		return MemoryStore::delete($this->identifier());
	}
	
	static public function primary_keys() {
		$primary_keys = array();
		foreach (static::$properties as $name => $info) {
			if (in_array("primary_key", $info)) {
				$primary_keys[] = $name;
			}
		}
		if (count($primary_keys) == 0) {
			$class = get_called_class();
			throw new FuzzyRecordException("Primary keys must be set for $class");
		}
		return $primary_keys;
	}
	
	static protected function sorters() {
		$sorters = array();
		foreach (static::$properties as $name => $info) {
			if (array_key_exists("sorter", $info)) {
				$sorters[] = $name;
			}
		}
		return $sorters;
	}
	
	static protected function search_fields() {
		$search_fields = array();
		foreach (static::$properties as $name => $info) {
			if (in_array("searchable", $info)) {
				$search_fields[] = $name;
			}
		}
		return $search_fields;
	}

	static protected function like_search_fields() {
		$like_search_fields = array();
		foreach (static::$properties as $name => $info) {
			if (in_array("like_searchable", $info)) {
				$like_search_fields[] = $name;
			}
		}
		return $like_search_fields;
	}

	static protected function select_keys_sql() {
		return DB::$db_quote_mark.implode(DB::$db_quote_mark.",".DB::$db_quote_mark,static::primary_keys()).DB::$db_quote_mark;
	}
	
/*
DB type setup
*/
	// Called once to setup db prefs
	public static function init() {
		switch (DB_TYPE) {
			case "pdo-mysql":
				require_once("lib/DB_MySQL.php");
				break;
			case "pdo-pgsql":
				require_once("lib/DB_PostgreSQL.php");
				break;
		}
	}

}
