<?php
set_include_path(get_include_path().":.:../");
require_once("bootstrap.php");

$tests = scandir("../tests");
$classes = array();
foreach ($tests as $test) {
	if (mb_substr($test,-4) != ".php") {
		continue;
	}
	$class = mb_substr($test,0,mb_strlen($test)-4);
	$obj = new $class();
	if ($obj instanceof FuzzyTest) {
		$classes[] = $class;
	}
}

FuzzyTest::reset();
FuzzyTest::run_tests_for_classes($classes);

require_once("views/test_results.php");
exit;

