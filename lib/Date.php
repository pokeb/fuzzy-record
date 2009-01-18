<?php
class Date {

	public $year;
	public $month;
	public $day;
	public $hour;
	public $minute;
	public $second;
	
	public $timezone_offset;

	public function __construct($date="") {
	
		if (is_string($date) && $date != "") {
		
			// Match ISO 8601 or similar
			preg_match("/([0-9]{4})\-([0-9]{2})\-([0-9]{2})(\s|T)([0-9]{2})\:([0-9]{2})\:([0-9]{2})((\s{0,1})([\+|\-]{0,1})([0-9]{2})(\:{0,1})([0-9]{2}){0,1})/",$date,$matches);
			if (count($matches) > 0) {
				$this->year = $matches[1];
				$this->month = $matches[2];
				$this->day = $matches[3];
				$this->hour = $matches[5];
				$this->minute = $matches[6];
				$this->second = $matches[7];
				
				$offset = "";
				if ($matches[10] != "") {
					$offset = $matches[10];
				} else {
					$offset = "+";
				}
				if ($matches[11] != "" && $matches[13] != "") {
					$offset .= $matches[11].$matches[13];
				}
				if ($offset == "+") {
					$this->timezone_offset = "+0000";
				} else {
					$this->timezone_offset = $offset;
				}
			
			// Fallback to PHP date handling (won't work for dates before 1901!)
			} else {
				$this->create_from_timestamp(strtotime($date));
			}
		} elseif (is_int($date)) {
			$this->create_from_timestamp($date);	
		
		} else {
			$this->create_from_timestamp(mktime());	
		}
	}
	
	public function db_date_with_time() {
		return $this->year."-".$this->month."-".$this->day." ".$this->hour.":".$this->minute.":".$this->second;
	}
	
	public function db_date_with_time_and_timezone() {
		return $this->db_date_with_time().$this->timezone_offset;
	}
	
	public static function now() {
		return new Date("Y-m-d H:i:s");
	}
	
	protected function create_from_timestamp($timestamp) {
		$this->year = date("Y",$timestamp);
		$this->month = date("m",$timestamp);
		$this->day = date("d",$timestamp);
		$this->hour = date("H",$timestamp);
		$this->minute = date("i",$timestamp);
		$this->second = date("s",$timestamp);	
		$this->timezone_offset = "+0000"; //Default to UTC
	}

}