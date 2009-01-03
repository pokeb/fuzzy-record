<?php

define("SITE_PATH","/Users/ben/Sites/fuzzyrecord");

define("DB_SERVER","127.0.0.1");
define("DB_USER","root");
define("DB_PASSWORD","");
define("DB_DATABASE","framework-starter");
define("DB_TYPE","pdo-mysql");

MemoryStore::$use_in_memory_store = true;
MemoryStore::$use_memcache_store = false;

//MemoryStore::$memcache_servers = array(array("host" => '127.0.0.1', "port" => 11211));