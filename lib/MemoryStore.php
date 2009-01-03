<?php

class MemoryStore {

	public static $shared_memcache_store;
	public static $use_memcache_store;
	public static $use_in_memory_store;
	public static $memcache_servers = array();
	
	static protected $objects = array();
	
	static public function read($key) {
		if (MemoryStore::$use_in_memory_store) {
			if (array_key_exists($key,MemoryStore::$objects)) {
				//error_log("Read $key from in-memory store",0);
				return MemoryStore::$objects[$key];
			}
		}
		
		if (MemoryStore::$use_memcache_store) {
			if (MemoryStore::memcached_connect()) {
				$cached_object = MemoryStore::$shared_memcache_store->get($key);
				if ($cached_object !== false) {
					if (MemoryStore::$use_in_memory_store) {
						MemoryStore::$objects[$key] = $cached_object;
					}
					//error_log("Read $key from memcache store",0);
					return $cached_object;
				}
			}
		}

		return NULL;
	}
	
	static public function write($key,$value) {
		if (MemoryStore::$use_in_memory_store) {
			MemoryStore::$objects[$key] = $value;
		}
		if (MemoryStore::$use_memcache_store) {
			if (MemoryStore::memcached_connect()) {
				if (MemoryStore::$shared_memcache_store->set($key, $value, false, 3600)) {
	
					return true;
				}
				error_log("MEMCACHE: Caching failed for '$key'",0);
				MemoryStore::$use_memcache_store = false;
			}
		}
		return false;
	}
	
	static public function delete($key) {
		if (MemoryStore::$use_in_memory_store) {
			if (array_key_exists($key,MemoryStore::$objects)) {
				unset(MemoryStore::$objects[$key]);
			}
		}
		if (MemoryStore::$use_memcache_store) {
			if (MemoryStore::memcached_connect()) {
				if (MemoryStore::$shared_memcache_store->delete($key,5)) {
					return true;
				}
				error_log("MEMCACHE: Delete failed for '$key'",0);
				MemoryStore::$use_memcache_store = false;
			}
		}
		return false;
	}
	
	static public function flush() {
		if (!MemoryStore::$use_memcache_store) {
			return false;
		}
		$objects = array();
		if (MemoryStore::$use_memcache_store && MemoryStore::memcached_connect()) {
			return (MemoryStore::$shared_memcache_store->flush());
		}
	}
	
	static protected function memcached_connect() {
	
		if (!MemoryStore::$use_memcache_store) {
			return false;
		} elseif (isset(MemoryStore::$shared_memcache_store)) {
			return true;
		}
		
		if (!extension_loaded('memcache')) {
			MemoryStore::$use_memcache_store = false;
			error_log("MEMCACHE: Cannot use memcache store because memcache extension is not loaded",0);
			return false;
		}
	
		MemoryStore::$shared_memcache_store = new Memcache();
		$connected_to_a_server = false;
		foreach (MemoryStore::$memcache_servers as $server) {
			if (MemoryStore::$shared_memcache_store->addServer($server['host'],$server['port'],true)) {
				
				//print_r(MemoryStore::$shared_memcache_store->getExtendedStats());
				//Make sure server is up
				if (MemoryStore::$shared_memcache_store->getServerStatus($server['host'],$server['port']) !== 0) {
					$connected_to_a_server = true;
				}
			}
		}
		
		if (!$connected_to_a_server) {

			MemoryStore::$use_memcache_store = false; //Failed to connect to any servers
			error_log("MEMCACHE: Failed to connect to memcache store, will ignore for the rest of this request",0);

			return false;
		} else {
			return true;
		}
	}

}