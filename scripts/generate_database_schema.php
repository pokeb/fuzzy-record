<?php
set_include_path(get_include_path().":.:../");
require_once("bootstrap.php");

$paths = array("models");

foreach ($paths as $path) {  
	$models = scandir("../$path");

	foreach ($models as $model) {
		if (mb_substr($model,-4) != ".php") {
			continue;
		}
	
		$class = mb_substr($model,0,mb_strlen($model)-4);
		$obj = new $class();
		if ($obj instanceof FuzzyRecord && $class != "FuzzyRecord") {
			
			echo $obj->create_table_sql()."<br />";
		}
	}
}