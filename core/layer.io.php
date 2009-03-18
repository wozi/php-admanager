<?php
/**
 *	Class io
 *	Abstraction layer for IO accesses (local or distant)
 *	Provides read/write accesses, + cache and versionning capabilities
 *	@layer
 *	@version 0.1.0.20 - 28/12/2008
 *	Takes care of all IO actions
 */

/**
 *	CACHE_DIR
 *	Must be set to your CACHE directory. Make sure it is writeable.
 */
define ("CACHE_DIR", "../cache/");

/**
 *	DISTANT_CACHE_EXCEED
 *	Set how many minutes we keep a cache for a distant access
 */
define ("DISTANT_CACHE_EXCEED", 30);

class io {
	
	/**
	 *	read ()
	 *	Read a file
	 */
	public function read ($path) {
		if (function_exists ("resolvePath")) $path = resolvePath ($path);
			
		/**
		 *	Check if $path is an URL, in which case we will use readDistant instead.
		 */
		if (preg_match ('@^(?:http://)@i', $path))
			return Net::get ($path);
		
		if (!file_exists ($path)) return NULL;	 
		$fr = fopen ($path, 'r');
		$data = fread ($fr, filesize ($path));
		fclose ($fr);
		
		return $data;
	}
	
	/**
	 *	write ()
	 *	Write a file
	 */
	public function write ($filename, $content) {
	//	if (function_exists ("resolvePath")) $filename = resolvePath ($filename);
	
		if (($fw = fopen ($filename, "w+")) === FALSE) {
			return false;
		}
		fwrite ($fw, $content, strlen ($content));
		fclose ($fw);
		
		return true;
	}
	
	/**
	 *	move ()
	 *	Move a file
	 */
	public function move ($orig_name, $target_name) {
		if (function_exists ("resolvePath")) $orig_name = resolvePath ($orig_name);
		if (function_exists ("resolvePath")) $target_name = resolvePath ($target_name);
		
		chmod ($target_name, '777');
		
		return move_uploaded_file ($orig_name, $target_name);
	}
	
	/**
	 *	mod_time ()
	 *	Get the last mod time of a file
	 *	@param {String} path
	 *	@return {String} the date, NULL if the file is not found
	 */
	public function mod_time ($path) {
		if (function_exists ("resolvePath")) $path = resolvePath ($path);

		if (file_exists ($path))
			return filemtime ($path);
		else
			return NULL;
	}
	
	/**
 	 *	copy ()
 	 *	Copy a file to another location
 	 *	@param {String} source : the source path
 	 *	@param {String} dest : the dest path. If this is a dir, we'll use the same filename.
 	 *	@return {Boolean} if success or failed
 	 */
	public function copy ($source, $dest) {
		if (function_exists ("resolvePath")) $source = resolvePath ($source);
		if (function_exists ("resolvePath")) $dest = resolvePath ($dest);
		if (!file_exists ($source)) return false;
		
		/**
		 *	Get the file's content
		 */
		if (($file_content = self::read ($source)) === NULL) {
			echo "Copy: Failed reading source file content<br>";
			return false;
		}
		
		/**
 		 *	Now get the type of copy. If we have a filename for dest file, we'll use it. If not, we'll use the same name
 		 */
 		if (is_dir ($dest)) {
 			$dest .= basename ($source);
 		}
 		
 		/**
 		 *	Now write the file to this folder
 		 */
 		if (!self::write ($dest, $file_content)) return false;
 		
 		return true;
	}
	
	/**
	 *	CACHE *****************************************************
	 */
	
	/**
	 *	cache ()
	 *	Write a file into the cache.
	 *	It can be use to cache a file by doing a copy, or to cache some content so a new file will be created
	 *	@param {String} path : the path of the file to be cached
	 *	@param {String} file_content [] : if set, this content will be written into a file named with the given path
	 *	@return {Boolean} true if success, false if failed caching
	 *	
	 *	@ToDo : on devrait checker si la version en cache (si il y a) n'est pas déjà la dernière version
	 */
	public function cache ($path, $file_content=NULL) {
		if (function_exists ("resolvePath")) $path = resolvePath ($path);
		
		/**
		 *	Create the cache filename
		 */
		$cached_path = self::createCachedPath ($path);			
	
		/**
		 *	If file_content is not set, we'll make an exact copy of the given file in cache
		 */
		if (!$file_content) {
			if (!file_exists ($path)) return false;
			
			/**
			 *	Copy the file
			 */
			return self::copy ($path, $cached_path);
		
		/**
 		 *	If this is a content copy, we'll create a new file
 		 */
		} else {
			return self::write ($cached_path, $file_content);
		}
	}	
	
	/**
	 *	createCachedPath ()
	 *	Create a valid cache path
	 */
	public function createCachedPath ($path) {
		return CACHE_DIR.basename ($path).".cache";
	}
	
	/**
	 *	cacheUpToDate ()
	 *	Check if a file is up to date in the cache.
	 *	This will check the mod_time of the original file and the mod_time of the file we have
	 *	in our db
	 */	
	public function cacheUpToDate ($path) {
		if (function_exists ("resolvePath")) $path = resolvePath ($path);
		if (!file_exists ($path)) return false;
		
		if (self::mod_time ($path) < self::getCacheTime ($path)) return true;
		else return false;
	}
	
	/**
	 *	getCachePath ()
	 *	get the path in cache of a file that have been cached, from its original path
	 */
	public function getCachePath ($path) {
		return self::createCachedPath ($path);
	}
	
	/**
	 *	readCache ()
	 *	Get the content of a file that has been cached, from its original filename
	 */
	public function readCache ($path) {
		return self::read (self::createCachedPath ($path)); 
	}
	
	/**
	 *	getCacheTime ()
	 *	Get the time when a file has been cached, from its original path
	 */
	public function getCacheTime ($path) {
		return self::mod_time (CACHE_DIR.basename ($path).".cache");
	}
	
	/**
	 *	Filename Utils ********************************
	 */
	
	/**
	 *	createFileName ()
	 *	Create a valid filename
	 *	@param {String} filename : the filename to be converted. Can be a complete path.
	 *	@param {String} folder : the complete path to the file for access
	 *
	 *	@alpha
	 */
	private function createFileName ($filename, $folder) {
		//Control caracters
		$filename = fsLayer::sanitize ($filename);
		
		/**
		 *	Don't overwrite files. If filename already exists, we'll rename it
		 */
		if (file_exists ($folder.$filename)) $filename = self::createAlternativeFilename ($folder, $filename);
		
		return $filename;
	}
	
	/**
 	 *	sanitize ()
 	 *	Sanitize a filename
 	 *	@param {String} filename : the filename to sanitize
 	 */
	public function sanitize ($filename) {
		$filename = preg_replace ('/[,| ]/', '_', $filename);		//Replace , et espaces par _
		/** 
		 *	Attention! Le check sur les accents doit etre fait avant, sinon probleme d'encodage
		 */
		$filename = preg_replace ('/[é|è|ê|ë|Ã©]/', 'e', $filename);		//Replace les accents
		$filename = preg_replace ('/[à|â|ä]/', 'a', $filename);
		$filename = preg_replace ('/[ù|û]/', 'u', $filename);
		
		/**
		 *	Remove any . in the filename except for the extension
		 */
		$filename = preg_replace ('/^.[.]+\.'.self::getFileExtension ($filename).'$/', '_', $filename);
		
		return $filename;
	}
	
	/**
	 *	getFileExtension ()
	 *	Get the extention of a file
	 */
	public function getFileExtension ($filename) {
		$extension = array ();
		preg_match ('/^.[.]+$/', $filename, $extension);
		return $extension [0];
	}
	
	/**
	 *	createAlternativeFilename ()
	 *	Create an alternative filename, in case the file already exists
	  *
	 *	@alpha
	 */
	private function createAlternativeFilename ($folder, $filename, $i=1) {
		/**
		 *	The nalternative filename will be something like filename [1].jpg
		 *	@note : at this point, we should not have any . in the filename, except for the extension
		 */
		$file_exp = explode ('.', $filename);
		$alt_filename = $file_exp [0].' ['.$i.'].'.$file_exp [1];
		
		if (file_exists ($folder.$alt_filename)) return self::createAlternativeFilename ($folder, $filename, $i++);
		return $alt_filename;
	}
	
	/**
	 *	Mime Type Utils ********************************
	 */
	
	/**
	 *	getFileType ()
	 *	Get the file type of a file
	 *	@param {String} file : the file to analyze
	 */
	public function getFileType ($file) {
		if (eregi ('(.jpg|.jpeg|.gif|.png|.bmp)$', $file)) return "Image";		//A changer en regex PERL et preg_match
		else if (eregi ('(.zip|.rar|.ace|.tar|.gz)$', $file)) return "Archive";
		else if (eregi ('(.doc|.xls|.ppt)$', $file)) return "Office Document";
		else return "Unknown";
	}
	
	/**
	 *	getMimeType ()
	 *	Get the mime type of a file
	 *	@param {String} file : the file to analyze
	 */
	public function getMimeType ($file) {
		//TO DO
		return "x/text";
	}
	
	/**
	 *	_error ()
	 *	Raise an error.
	 *	I fthe Debugger class exists (part of vera) we'll use it.
	 *	If not, we'll display the message not to depend on it so this file can be use in standalone.
	 *	@param {Int} level : error level (from 0 to 5)
	 *	@param {String} message : the message
	 */
	private function _error ($level, $message) {
		if (class_exists ('Debugger')) {
			return Debugger::error ($level, $message);
		} else {
			echo "Error: ".$level."<br>";
			return false;
		}
	}
}
?>