<?php

class FuzzyTest {
	
	static $results = array();
	static $test_failures = 0;
	
	public function __construct() {
		assert_options(ASSERT_WARNING, 0);
		assert_options(ASSERT_CALLBACK, 'FuzzyTest::record_test_failure');
	}
	
	public function reset() {
		static::$results = array();
		static::$test_failures = 0;
	}
	
	public function run_tests_for_classes($classes) {
		foreach ($classes as $class) {
			$obj = new $class();
			$obj->run_all_tests();
		}
	}
	
	public function run_all_tests() {
		$reflection_obj = new ReflectionClass(get_class($this));
		$methods = $reflection_obj->getMethods();
		foreach ($methods as $method) {
			$name = $method->getName();
			if (substr($name,0,5) == "test_") {
				$this->$name();
			}
		}
	}
	

	
	public static function assert_equal($arg1,$arg2,$message) {
		if (assert('$arg1 == $arg2; //* '.$message)) {
			static::record_test_success();
		}
	}
	
	public static function assert_not_equal($arg1,$arg2,$message) {
		if (assert('$arg1 != $arg2; //* '.$message)) {
			static::record_test_success();
		}
	}	
	
	public static function assert_true($arg1,$message) {
		if (assert('($arg1); //* '.$message)) {
			static::record_test_success();
		}
	}

	public static function assert_false($arg1,$message) {
		if (assert('!($arg1); //* '.$message)) {
			static::record_test_success();
		}
	}
	
	protected static function record_test_success() {
		try {
			throw new Exception();
		} catch (Exception $e) {
			$trace = $e->getTrace();
			$test_run = new TestRun;
			$test_run->function = $trace[2]['function'];
			$test_run->class = $trace[2]['class'];
			$test_run->file = $trace[1]['file'];
			$test_run->success = true;
			
			static::$results[] = $test_run;
		}
	}
	
	protected static function record_test_failure($file, $line, $code) {
		try {
			throw new Exception();
		} catch (Exception $e) {
			$message = explode("//*",$code);
			$message = $message[1];
			$trace = $e->getTrace();
			
			$test_run = new TestRun;
			$test_run->function = $trace[3]['function'];
			$test_run->class = $trace[3]['class'];
			$test_run->file = $trace[2]['file'];
			$test_run->fail_line = $trace[2]['line'];
			$test_run->fail_message = $message;
			$test_run->success = false;
			
			
			static::$results[] = $test_run;
			static::$test_failures++;
			
		}
	}
}

class TestRun {
	public $file;
	public $class;
	public $function;
	public $success;
	public $fail_line;
	public $fail_message;
}

