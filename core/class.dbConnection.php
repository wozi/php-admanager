<?php
/**
 *	Class dbController
 *	Manage all database accesses
 *	@inherit controller
 */

class dbController {
	/**
	 *	con
	 *	Our connection object, when connected
	 */
	var $con = NULL;
	
	/**
	 *	host
	 *	The host value
	 */
	var $host = NULL;
	
	/**
	 *	login
	 *	The login to use to connect to the database
	 */
	var $login = NULL;
	
	/**
	 *	password
	 *	The password required to connect to the database
	 */
	var $password = NULL;
	
	public function __construct () {}
	
	/**
	 *	init ()
	 *	Called when the cotnroller is created
	 *	At this point, we need to init our connection values. We take everything from the main config file within the db section.
	 */
	public function init ($host, $user, $pwd) {
		$this->host = $host;
		$this->login = $user;
		$this->password = $pwd;
	}	

	/**
	 *	connect ()
	 *	Connect to the database.
	 *	This function has to be called each time before a query is made, just because the resource is not serialized!!
	 */
	public function connect () {
		//We should try to check if the resource is already allocated.
		if (($this->con = mysql_connect ($this->host, $this->login, $this->password)) != -1) return true;
		else return $this->_error (2, "db: Cannot connect to the database.");
	}
	
	/**
	 *	free ()
	 *	Release the connection resource
	 */
	public function free () {
		$this->con = NULL;
	}
	
	/**
	 *	select_db ()
	 *	Select a database
	 *	@param {String} dbname : the db name to connect to
	 *	@return {Boolean} whether successful or not
	 */
	public function select_db ($dbname) {
	//	if (mysql_select_db ($dbname, $this->con) != -1) {
		
		/**
		 *	Try to reconnect if deconnected (from a serialization)
		 */
		if (!$this->con) $this->connect ();
		
		mysql_select_db ($dbname, $this->con);
			$this->selected_db = $dbname;
	//		return true;
	//	} else
	//		return false;
	}
	
	/**
	 *	insert ()
	 *	Insert an entry in a database
	 */
	public function insert ($table, $elements) {
		$query = "INSERT INTO ".$table." (";
		$query2 = "";
		
		foreach ($elements as $field_name=>$value) {
			$query .= $this->secure ($field_name).",";
			$query2 .= "'".$this->secure ($value)."',";
		}
		$query = substr ($query, 0, strlen ($query) - 1);		//Remove the last ,
		$query2 = substr ($query2, 0, strlen ($query2) - 1);		//Remove the last ,
		
		$q = $query.") VALUES (".$query2.")";
		
		//TO DO : vérifier les insertions avec les valeurs retorunées et vérfieri avant insert par le security::checkDb ();
		$res = mysql_query ($q);
		
		return true;
	}
	
	/**
	 *	select_all ()
	 *	Do a select * on a table and return all the elements.
	 */
	public function select_all ($table) {
		$query = "SELECT * FROM ".$table;
		if (($res = mysql_query ($query)))
			return $res;
		else 
			return NULL;
	}
	
	/**
	 *	update ()
	 */
	public function update ($table, $what, $value, $where) {
		$query = "UPDATE ".$table." SET ".$what."='".$value."' WHERE ".$where;
		if (($res = mysql_query ($query)))
			return true;
		else 
			return false;
	}
	
	/**
	 *	select()
	 *	Do a select  on a table and return the elements.
	 *	@param {Array} what 
	 *	@param {Array} conditions 
	 *	@a changer
	 */
	public function select ($table, $what=NULL, $conditions='') {	
		$query = "SELECT * FROM ".$table.$conditions;
		if (($res = mysql_query ($query)))
			return $res;
		else 
			return NULL;
	}
	
	/**
	 *	count ()
	 *	Make a count query
	 *	A adapter en tableaux what et where
	 */
	public function count ($table, $what, $where) {
		$query = "SELECT COUNT(*) FROM ".$table." ".$where;
		if (($res = mysql_query ($query)))
			return $res;
		else 
			return NULL;
	}
	
	/**
	 *	secure ()
	 *	Secure the SQL value
	 */
	private function secure ($value) {
	    if (get_magic_quotes_gpc ()) {
	          $value = stripslashes ($value);
	    }
	    $value = mysql_real_escape_string ($value);
	    return $value;
	}
}
?>