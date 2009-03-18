<?php
/**
 *	Abstraction layer for the database
 */
class dbLayer {
	var $host = NULL;
	var $user = NULL;
	var $pwd = NULL;
	
	// the base name to use
	var $base = NULL;

	/**
	 *	Constructor ()
	 */
	function __construct ($host, $user, $pwd, $base) {
		$this->host = $host;
		$this->user = $user;
		$this->pwd = $pwd;
		$this->base = $base;
	}
	
	/**
	 *	getPassword ()
	 *	Get the password from the email
	 *	@return user []
	 */
	public function getPassword ($email) {
		//Connect to the database
		$db = new dbController ();
		$db->init ($this->host, $this->user, $this->pwd);
		$db->select_db ($this->base);
		
		$users = $db->select ('_accounts', NULL, " WHERE email='".$email."'");
		
		$password = false;
		while ($user = mysql_fetch_array ($users)) {
			return $user;
		}
		return false;
	}
	
	/**
	 *	getLastName ()
	 *	Get the last name of a person from DB
	 */
	public function getLastName ($sma) {
		//Connect to the database
		$db = new dbController ();
		$db->init ($this->host, $this->user, $this->pwd);
		$db->select_db ($this->base);
		
		$users = $db->select ('_accounts', NULL, " WHERE sma='".$sma."'");
		
		while ($user = mysql_fetch_array ($users)) {
			return $user ['lastname'];
		}
		return '';
	}
	
	/**
	 *	updatePassword ()
	 *	Update the password of an user
	 */
	public function updatePassword ($sma, $password, $hash_password=false) {
		//Connect to the database
		$db = new dbController ();
		$db->init ($this->host, $this->user, $this->pwd);
		$db->select_db ($this->base);
		
		// hash the password if hashing is activated
		if ($hash_password) 
			$password = sha1 ($password);
		
		return $db->update ('_accounts', 'password', $password, "sma='".$sma."'");
	}
	
	/**
	 *	getUserEmail ()
	 */
	public function getUserEmail ($firstname, $lastname) {
		//Connect to the database
		$db = new dbController ();
		$db->init ($this->host, $this->user, $this->pwd);
		$db->select_db ($this->base);
		
		$users = $db->select ('_accounts', NULL, " WHERE firstname='".$firstname."' AND lastname='".$lastname."'");
		
		while ($data = mysql_fetch_array ($users)) {
			return $data ['email'];
		}
		return false;
	}
	
	/**
	 *	getUserBySma ()
	 */
	public function getUserBySma ($sma) {
		//Connect to the database
		$db = new dbController ();
		$db->init ($this->host, $this->user, $this->pwd);
		$db->select_db ($this->base);
		
		$users = $db->select ('_accounts', NULL, " WHERE sma='".$sma."'");
		
		while ($data = mysql_fetch_array ($users)) {
			return $data;
		}
		return false;
	}
	
	/**
	 *	getEmails ()
	 *	Get all emails
	 */
	public function getEmails () {
		//Connect to the database
		$db = new dbController ();
		$db->init ($this->host, $this->user, $this->pwd);
		$db->select_db ($this->base);
		
		$users = $db->select ('_accounts', NULL, NULL);
		
		$ret = array ();
		while ($data = mysql_fetch_array ($users)) {
			array_push ($ret, $data ['email']);
		}
		return $ret;
	}
	
	/**
	 *	addUserToDb ()
	 *	Add an user to the database
	 */
	public function addUserToDb ($sma, $firstname, $lastname, $email, $bu, $password, $hash_password=false) {
		//Connect to the database
		$db = new dbController ();
		$db->init ($this->host, $this->user, $this->pwd);
		$db->select_db ($this->base);
		
		//Insert the shit
		$insert = array ();
		$insert ['sma'] = $sma;
		$insert ['firstname'] = $firstname;
		$insert ['lastname'] = $lastname;
		$insert ['email'] = $email;
		$insert ['bu'] = $bu;
		
		// password hashing can be deactivated if you don't want to use it.
		// @note If hashing is activated, the get password feature will be dactivated!! Only change will be possible!
		if ($hash_password) {
			$insert ['password'] = sha1 ($password);
		} else
			$insert ['password'] = $password;
		
		$published = $db->insert ('_accounts', $insert);
		
		return $published;
	}
}
?>