<?php

class DateTest extends FuzzyTest {
	
	//Setup data for these tests
	public function __construct() {
	}
	
	
	public function test_basic_operation() {
	
		$date = new Date();
		FuzzyTest::assert_equal($date->year,date("Y"),"Date created with wrong year");	
		FuzzyTest::assert_equal($date->month,date("m"),"Date created with wrong month");	
		FuzzyTest::assert_equal($date->day,date("d"),"Date created with wrong day");	
		FuzzyTest::assert_equal($date->hour,date("H"),"Date created with wrong hour");	
		FuzzyTest::assert_equal($date->minute,date("i"),"Date created with wrong minute");	
		FuzzyTest::assert_equal($date->second,date("s"),"Date created with wrong second");	
		FuzzyTest::assert_equal($date->timezone_offset,"+0000","Date created with timezone offset");	
		
		$date = new Date(mktime());
		FuzzyTest::assert_equal($date->year,date("Y"),"Date created with wrong year");	
		FuzzyTest::assert_equal($date->month,date("m"),"Date created with wrong month");	
		FuzzyTest::assert_equal($date->day,date("d"),"Date created with wrong day");	
		FuzzyTest::assert_equal($date->hour,date("H"),"Date created with wrong hour");	
		FuzzyTest::assert_equal($date->minute,date("i"),"Date created with wrong minute");	
		FuzzyTest::assert_equal($date->second,date("s"),"Date created with wrong second");	
		FuzzyTest::assert_equal($date->timezone_offset,"+0000","Date created with timezone offset");	

		$date = new Date(date("d F Y H:i:s"));
		FuzzyTest::assert_equal($date->year,date("Y"),"Date created with wrong year");	
		FuzzyTest::assert_equal($date->month,date("m"),"Date created with wrong month");	
		FuzzyTest::assert_equal($date->day,date("d"),"Date created with wrong day");	
		FuzzyTest::assert_equal($date->hour,date("H"),"Date created with wrong hour");	
		FuzzyTest::assert_equal($date->minute,date("i"),"Date created with wrong minute");	
		FuzzyTest::assert_equal($date->second,date("s"),"Date created with wrong second");	
		FuzzyTest::assert_equal($date->timezone_offset,"+0000","Date created with timezone offset");	
		
		$date = new Date(date("c"));
		FuzzyTest::assert_equal($date->year,date("Y"),"Date created with wrong year");	
		FuzzyTest::assert_equal($date->month,date("m"),"Date created with wrong month");	
		FuzzyTest::assert_equal($date->day,date("d"),"Date created with wrong day");	
		FuzzyTest::assert_equal($date->hour,date("H"),"Date created with wrong hour");	
		FuzzyTest::assert_equal($date->minute,date("i"),"Date created with wrong minute");	
		FuzzyTest::assert_equal($date->second,date("s"),"Date created with wrong second");	
		FuzzyTest::assert_equal($date->timezone_offset,"+0000","Date created with timezone offset");	
		
	}
	
}
	