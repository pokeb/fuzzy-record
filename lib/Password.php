<?php

class Password {

	static function generate_salt() {
		return substr(md5(uniqid(rand(),true)),0,32);
	}

	static function hash($password,$salt) {
		return sha1($password.$salt);
	}
	
}