<?php
	/**
	 * Copyright: Deux Huit Huit 2014
	 * LICENCE: MIT https://deuxhuithuit.mit-license.org
	 */
	
	if(!defined("__IN_SYMPHONY__")) die("<h2>Error</h2><p>You cannot directly access this file</p>");
	
	class CacheableFetch {
		
		private $className;
		private $cache = array();
		
		/**
		 * @param string $className
		 */
		public function __construct($className) {
			$this->className = $className;
		}
		
		public function fetch($id, $secondId = null) {
			$args = func_get_args();
			if (!$id || $secondId || is_array($id)) {
				$id = sha1(serialize($args));
			}
			if ($id && isset($this->cache[$id])) {
				return $this->cache[$id];
			}
			$ret = forward_static_call_array(array($this->className, 'fetch'), $args);
			if ($id) {
				if (is_array($ret)) {
					foreach ($ret as $key => $value) {
						$this->cache[$key] = $value;
					}
				} else {
					$this->cache[$id] = $ret;
				}
			}
			return $ret;
		}
		
		public function clear() {
			$this->cache = array();
		}
	}