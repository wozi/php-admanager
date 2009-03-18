<?php
/**
 *	Class Config
 *	Takes care of the configuration of Kobaia. It is accessible by the controllers, processes and libs.
 *	Based on a YAML config file.
 *	Takes care of caching the file (ioLayer is needed to enable this feature).
 *
 *	@version 0.2 // 14112008 ++ 07/01/2009
 *
 *	@Supports:
 *		- load simple YAML file as array
 *		- apply conditions (if x in y)
 *
 *	@todo:
 *		- Implements other conditions + finish If
 */
//include ('lib/spyc/class.spyc.php');
//include ('layer/layer.io.php');

define ("YAML_CACHE_DIR", "../cache/config");		//A CHANGER !!!!

class Config {

	/**
	 *	yaml
	*	Our YAML array, containing the configuration information
	 */
	var $yaml = NULL;

	/**
	 *	tree
	 *	If the tree mode is enabled, this variable will contain the whole configuration as a DOM tree.
	 */	
	var $tree = NULL;
	
	
	/**
	 *	Constructor
	 *	Load a new config from the specified file
	 *	@param {String} configPath : the path to the YAML config file
	 */
	function __construct ($configPath=NULL) {
		if ($configPath) $this->load ($configPath);
	}
	
	/**
	 *	load ()
	 *	Load a config file
	 *
	 *	@param {String} configPath : the path to the YAML config file
	 *	@param {Boolean} createTree : if set to true, a tree will be created and any value will be accessible using the ->tree property
	 *	@return {Boolean} true/false whether succeed or failed
	 *
	 *	@todo Implement the tree feature
	 */
	public function load ($configPath, $createTree=false) {
		if (!class_exists ("Spyc")) return false;
			
		/** 
		 *	Cache the file if the ioLayer is loaded
		 */
		if (class_exists ('io')) {
			if (file_exists ($configPath)) {
				if (($conf = $this->cacheYaml ($configPath)) != FALSE) {
					$this->yaml = $conf;
					return true;
				} else
					$this->yaml = Spyc::YAMLLoad ($configPath);		//If caching failed
					return true;
				}
		} else {
			if (file_exists ($configPath)) {
				$this->yaml = Spyc::YAMLLoad ($configPath);
				return true;
			}
		}
		return false;
	}	
	

	/**
	 *	convertSpecialEncapsulation ()
	 *	Convert special encapsulations such as [ ... ] which means that the stuff inside is a PHP call
	 */
	private function convertSpecialEncapsulation ($value) {
		/**
		 *	Look for PHP encapsulation, put between brackets [ ... ]
		 */
		if (preg_match_all ("/\[ (.+?) \]/i", $value, $matches)) {
			eval ("\$result = ".$matches [1] [0].";");
			return $result;
		}
		return $value;
	}
	
	/** 
	 *	cacheYaml ()
	 *	Write a yaml file into a file as an array for cache.
	 *	We serialize the yaml array, and we just write it to a file.
	 *	@param {String} config_file : the path of the config file
	 *	@return {Array} the yaml array
	 */
	public function cacheYaml ($config_file) {
		/**
		 *	Create the cache name
		 */
		$cache_name = preg_replace ("/\//", "-", $config_file);
		$cache_name = preg_replace ("/[..\/]/", "", $config_file);
		$cached_file = YAML_CACHE_DIR.basename ($cache_name).".cache";
		
		/** 
		 *	if the file is out dated, we'll recache it
		 */
		if (io::mod_time ($cached_file) < io::mod_time ($config_file)) {
			/**
			 *	Serialize the data for write
			 */
			$yaml = Spyc::YAMLLoad ($config_file);
			$content = serialize ($yaml);
			
			/** 
			 *	Now let's write it with the cache option
			 */
			if (io::write ($cached_file, $content) === FALSE) {
				return false;
			}
		}
		
		/** 
		 *	Now read it and deserialize it.
		 */
		return unserialize (io::read ($cached_file));
	}
	
	/**
	 *	get ()
	 *	Get a value from a key
	 *	@param {String} key : the key to the value
	 *	@param {String} in : optionnal, take the value from another key element (inside/embed element)
	 *	@return {String} the value or NULL if not found
	 */
	function get ($key, $in=NULL) {
		foreach ($this->yaml as $ykey=>$yvalue) {
			// We may want to loonk for a specific value into that
			if ($in) {
				if ($ykey === $in) {
					// We've found the right key, now we'll look for a value into that
					foreach ($yvalue as $yykey=>$yyvalue) {
						if ($yykey == $key) {
							return $yyvalue;		//Got it!
						}
					}
				}
			}
			else {
				if ($ykey == $key) return $yvalue;
			}
		}
		return NULL;
	}
	
	/**
	 *	getv ()
	 *	Get a value but use a defautl value if not found
	 */
	public function getv ($key, $default_value) {
		// get the value
		if (($v = $this->get ($key)) !== NULL) return $v;
		else return $default_value;
	}
	
	/**
	 *	getAll ()
	 *	Get the complete config tree
	 *	@return {Array} 
	 */
	public function getAll () {
		return $this->yaml;
	}
	
	/**
	 *	getParent ()
	 *	Get the parent value of a value
	 *	@param {String} key : the key to the value
	 *	@return {String} the value or NULL if not found
	 */
	public function getParent ($key) {
		foreach ($this->yaml as $ykey=>$value) {
			if (is_array ($value)) {
				foreach ($value as $invalue) {
					if ($invalue == $key) {
						return $ykey;		//Gotcha -> return the parent key
					}
				}
			} else {
				if ($value == $key) return $value;		//Nothing higher -> return this value
			}
		}
	}
}
?>