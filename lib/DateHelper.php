<?php

class DateHelper {
	
	public static function now() {
		return date("Y-m-d H:i:s");
	}
	
	public static function db_date($date) {
		return date("Y-m-d H:i:s",$date);
	}

}