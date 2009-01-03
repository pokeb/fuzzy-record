<?php

class FuzzyExceptionHandler {
	public static function handle_exception($exception) {
		require_once("views/_html_exception.php");
		die;
	}
}