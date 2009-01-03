<?php
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_http_input('UTF-8');
mb_language('uni');
mb_regex_encoding('UTF-8');
ob_start('mb_output_handler');

require_once('config/config.php');

// Prints a backtrace - override this to do your own exception handling
set_exception_handler('FuzzyExceptionHandler::handle_exception');

// You must call init() before using FuzzyRecord
FuzzyRecord::init();

function __autoload($class) {
	
	if (file_exists(SITE_PATH."/models/$class.php")) {
		require_once(SITE_PATH."/models/$class.php");
		return;
	} elseif (file_exists(SITE_PATH."/lib/$class.php")) {
		require_once(SITE_PATH."/lib/$class.php");
		return;
	} elseif (file_exists(SITE_PATH."/tests/$class.php")) {
		require_once(SITE_PATH."/tests/$class.php");
		return;
	}
	throw new Exception("Cannot find class '$class'");
}
