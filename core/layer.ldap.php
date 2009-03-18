<?php
/**
 *	layer.ldap
 *	@version 0.1 ALPHA
 *	@author Nicolas Wozniak
 */
 
// display output errors. Activate only in dev environment!
define ('DEBUG', false);

class ldapLayer {
	/**
	*	ad
	 *	Our ldap object
	 */
	var $ad = NULL;
	
	/**
	 *	server
	 *	The current opened server
	 */
	var $server = NULL;
	
	/**
	 *	user
	 *	The current logged user
	 */
	var $user = NULL;
	
	/**
	 *	password
	 *	The current logged password
	 */
	var $password = NULL;
	
	
	/**
	 *	Constructor ()
	 */
	public function __construct () {}
	
	/**
	 *	connect ()
	 *	Connect to the LDAP server
	 */
	public function connect ($server, $user='', $password='') {
		// Connect to the server
		if (($this->ad = @ldap_connect ($server)) === FALSE) 
			return $this->_error ("Could not connect to server ".$server);
		
		// Login to the server
		if (($bd = @ldap_bind ($this->ad, $user, $password)) === FALSE)
			return $this->_error ("Cannot get credentials");

		// Connected? Let's set our login information for future use
		$this->server = $server;
		$this->user = $user;
		$this->password = $password;

		return true;
	}
	
	/**
	 *	get ()
	 *	Get the people in one folder
	 *	@parma {String} folder
	 */
	public function get ($folder, $who='*') {
		if (!$this->ad) return $this->_error ("No opened connection has been found");
		
		$dn = "OU=".$folder.",OU=MA-USERS,DC=ma,DC=local";
		$filter = "(cn=".$who.")";
		$attributes = array ("*");
		
		$result = ldap_search ($this->ad, $dn, $filter, $attributes);
		$entries = ldap_get_entries ($this->ad, $result);

		return $entries;
	}
	
	/**
	 *	getUserBySma ()
	 *	Get an user by its SMA
	 *	@parma {String} folder
	 */
	public function getUserBySma ($sma) {
		if (!$this->ad) return $this->_error ("No opened connection has been found");
		
		$dn = "OU=MA-USERS,DC=ma,DC=local";
		$filter = "(samaccountname=".$sma.")";
		$attributes = array ("*");
		
		$result = ldap_search ($this->ad, $dn, $filter, $attributes);
		$entries = ldap_get_entries ($this->ad, $result);

		return $entries;
	}
	
	/**
	 *	userExistsByName ()
	 *	Check if a user is already in the folder by its full name
	 *	@param {String} username : the username, as Nicolas Wozniak
	 *	@param {String} folder: the folder in which the user should be
	 */
	public function userExistsByName ($username, $folder='') {
		/**
		 *	Check that a conenction is opened, if not we'll try to open one
		 */
		if (!$this->ad) {
			if (!self::connect ($server, $user, $password))
				return $this->_error ("No opened connection has been found");
		}
		
		/**
		 *	Format the right DN call : our user in test in MA-USERS at ma.local
		 */
		$dn = "OU=".strtolower ($folder).",OU=MA-USERS,DC=ma,DC=local";
		$filter = "(CN=".$username.")";
		$attributes = array ("*");
		
		$result = ldap_search ($this->ad, $dn, $filter, $attributes);
		$entries = ldap_get_entries ($this->ad, $result);
		
		if ($entries ['count'] > 0)
			return true;
		else
			return false;
	}
	
	/**
	 *	userExistsBySMA ()
	 *	Check if a user is already in the folder by its SMA
	 *	@param {String} sma : the username, as nico.wozi
	 *	@param {String} folder: the folder in which the user should be
	 */
	public function userExistsBySMA ($sma, $folder=NULL) {
		
		//samaccountname
		
		/**
		 *	Check that a conenction is opened, if not we'll try to open one
		 */
		if (!$this->ad) {
			if (!self::connect ($server, $user, $password))
				return $this->_error ("No opened connection has been found");
		}
		
		/**
		 *	Format the right DN call : our user in test in MA-USERS at ma.local
		 */
		if ($folder) $dn = "OU=".strtolower ($folder).",OU=MA-USERS,DC=ma,DC=local";
		else $dn = "OU=MA-USERS,DC=ma,DC=local";
		$filter = "(samaccountname=".$sma.")";
		$attributes = array ("*");
		
		$result = ldap_search ($this->ad, $dn, $filter, $attributes);
		$entries = ldap_get_entries ($this->ad, $result);
		
		if ($entries ['count'] > 0)
			return true;
		else
			return false;
	}
	
	/**
	 *	getUserEmail ()
	 *	Get the email of an user, by his full name (Nicolas Wozniak)
	 *	@param {String} username : the username, as Nicolas Wozniak
	 *	@param {String} folder: the folder in which the user should be
	 */
	public function getUserEmail ($username, $folder=NULL) {
		/**
		 *	Check that a conenction is opened, if not we'll try to open one
		 */
		if (!$this->ad) {
			if (!self::connect ($server, $user, $password))
				return $this->_error ("No opened connection has been found");
		}
		
		/**
		 *	Format the right DN call : our user in test in MA-USERS at ma.local
		 */
		if ($folder) $dn = "OU=".strtolower ($folder).",OU=MA-USERS,DC=ma,DC=local";
		else $dn = "OU=MA-USERS,DC=ma,DC=local";
		$filter = "(CN=".$username.")";
		$attributes = array ("*");
		
		$result = ldap_search ($this->ad, $dn, $filter, $attributes);
		$entries = ldap_get_entries ($this->ad, $result);
		
		if ($entries [0] ['mail'] ['count'] > 0)
			return $entries [0] ['mail'] [0];
		else
			return NULL;
	}
	
	/**
	 *	getUserByEmail ()
	 *	Get the user's details by his email
	 *	
	 *	
	 */
	public function getUserByEmail ($email, $folder='') {
		/**
		 *	First get the full list of people within this country
		 */
		$all_people = self::get ($folder);
		
		/**
		 *	Now browse it and try to locate the user with this email
		 */
		foreach ($all_people as $people) {
			if (is_array ($people)) {
				if (isset ($people ['mail'])) {
					if ($people ['mail'] ['count'] > 0) {
						if ($people ['mail'] [0] == $email)
							return $people;
					}
				}
			}
		}
		return NULL;
	}
	
	/**
	 *	add ()
	 *	Crreate a new user in our AD
	 *	@note connect () has to be called before to open the connection. Though, you can use directly use add but you'll have to specify the login infos (server, login, password)
	 *
	 *	Certficate Services name : sharepointac
	 *	CN=sharepointac,DC=ma,DC=local
	 */
	public function add ($username, $user_password, $firstname, $lastname, $folder, 
							$email='', $server='', $user='', $password='', $setNeverExpires=true) {
		/**
		 *	Check that a conenction is opened, if not we'll try to open one
		 */
		if (!$this->ad) {
			if (!self::connect ($server, $user, $password))
				return $this->_error ("No opened connection has been found");
		}
		
		/**
		 *	Format the right DN call : our user in test in MA-USERS at ma.local
		 */
		$dn = "CN=".$username.",OU=".strtolower ($folder).",OU=MA-USERS,DC=ma,DC=local";

		$user_data = array ();
		$user_data ['samaccountname'] = array ($username);
		$user_data ['userprincipalname'] = array ($username);
		$user_data ['sn'] = array ($lastname);
		$user_data ['mail'] = array ($email);
		$user_data ['givenname'] = array ($firstname);
		$user_data ['displayname'] = array ($firstname.' '.$lastname);
		$user_data ['objectclass'] = array (0 => 'top', 1 => 'person', 2 => 'organizationalPerson', 3 => 'user');
		
		if (!ldap_add ($this->ad, $dn, $user_data)) {
			print "Cannot create user ".$firstname.' '.$lastname." (".$username." - ".$user_password." - ".$email.") in 
						folder ".strtolower ($folder)."<br>";
			return false;
		}
		
		// Activate the account
		try {
			$ADSI = new COM ("LDAP:");
			$user = $ADSI->OpenDSObject ("LDAP://".$this->server."/".$dn, $this->user, $this->password, 1);
			$user->AccountDisabled = false;
			$user->SetInfo ();
			unset ($user);
			unset ($ADSI);
		} catch (Exception $e) {
			if (DEBUG) echo "Error: $e<br>";
			return false;
		}
		 
		// Set the password
		try {
			$ADSI = new COM ("LDAP:");
			$user = $ADSI->OpenDSObject ("LDAP://".$this->server."/".$dn, $this->user, $this->password, 1);
			$user->SetPassword ($user_password);
			$user->SetInfo ();
			unset ($user);
			unset ($ADSI);
		} catch (Exception $e) {
			if (DEBUG) echo "Error: $e<br>";
			return false;
		}
		
		// Set the Password Never Expires option
		// @read http://www.microsoft.com/technet/scriptcenter/resources/qanda/oct06/hey1031.mspx for more information.
		if ($setNeverExpires) {
			try {
				$ADSI = new COM ("LDAP:");
				$user = $ADSI->OpenDSObject ("LDAP://".$this->server."/".$dn, $this->user, $this->password, 1);
				$control = $user->Get ('userAccountControl') ^ 65536;
				$user->Put ("userAccountControl", $control);
				$user->SetInfo ();
				unset ($user);
				unset ($ADSI);
			} catch (Exception $e) {
				if (DEBUG) echo "Error: $e<br>";
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 *	changePassword ()
	 *	Change the password
	 */
	public function changePassword ($sma, $password, $bu) {
		/**
		 *	Check that a conenction is opened, if not we'll try to open one
		 */
		if (!$this->ad) {
			if (!self::connect ($server, $user, $password))
				return $this->_error ("No opened connection has been found");
		}
		
		/**
		 *	Format the right DN call : our user in test in MA-USERS at ma.local
		 */
		$dn = "CN=".$sma.",OU=".$bu.",OU=MA-USERS,DC=ma,DC=local";
		
		$ADSI = new COM ("LDAP:");
		$user = $ADSI->OpenDSObject ("LDAP://".$this->server."/".$dn, $this->user, $this->password, 1);
		if ($user->SetPassword ($password)) {
			$user->SetInfo ();
			unset ($user);
			unset ($ADSI);
			return true;
		} 
		unset ($user);
		unset ($ADSI);
		return true;
	}
	
	/**
	 *	_error ()
	 *	Break and print an error message out
	 *	@param {String} s : a message to print out
	 */
	private function _error ($s='') {
		if (DEBUG) print "[Error] ".$s."<br>";
		return false;
	}
}
?>