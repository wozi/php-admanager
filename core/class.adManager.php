<?php
/**
 *	Class gosharepoint
 *	Create a new Sharepoint User. Also do the verifications etc
 */
include ('layer.ldap.php');
include ('phpmailer/class.phpmailer.php');
include ('lib.mail.php');
include ('class.dbConnection.php');
include ('class.dbAccess.php');
include ('spyc/class.spyc.php');
include ('layer.config.php');


class adManager {

	// User informations
	var $firstname = '';
	var $lastname = '';
	var $bu = '';
	var $email = '';
	var $password = '';
	var $confirm_password = '';
	
	//For Change password
	var $old_password = '';
	var $login = '';
	
	// Tells if in creation process or not
	var $is_creating = false;
	
	// An array of the different erros encountered
	var $errors = array ();
	
	// Output messages after account creation
	var $messages = array ();
	
	// db connection
	var $dbLayer = NULL;
	
	// configuration object
	var $conf = NULL;
	
	
	/**
	 *	Constructor ()
	 *	Check if a creation has been sent and handle it if that's the case
	 */
	public function __construct () {
		// load the configuration
		$this->conf = new Config ('../config/app.yml');
	
		// CREATE ACCOUNT
		if (isset ($_POST ['gocreate'])) {
			// First get the data
			$this->getData ();
			
			// Then check if everything's all right
			if ($this->checkData ()) {
				// Go create
				if ($this->createUser ())
					$this->is_creating = true;
			}
			
		// GET PASSWORD
		} else if (isset ($_POST ['gogetpassword'])) {
			// First get the data (in that case, only email will be used)
			$this->getData ();
			
			if ($this->getPassword ())
				$this->is_creating = true;
		
		// CHANGE PASSWORD
		} else if (isset ($_POST ['gochangepassword'])) {
			// First get the data (in that case, only email will be used)
			$this->getData ();
			// + get some specific fields
			$this->old_password = $_POST ['oldpassword'];
			$this->login = $this->cleanInput ($_POST ['login']);
			
			if ($this->changePassword ())
				$this->is_creating = true;
		}
	}
	
	/**
	 *	initDb ()
	 *	Initialize the db layer
	 */
	private function initDb () {
		$this->dbLayer = new dbLayer ($this->conf->get ('addr', 'db'), 
										$this->conf->get ('login', 'db'), 
										$this->conf->get ('password', 'db'), 
										$this->conf->get ('base', 'db'));
	}
	
	/**
	 *	isCreateAccount ()
	 *	Active when no param or createaccount set
	 */
	public function isCreateAccount () {
		if (isset ($_GET ['createaccount'])) return true;
		else if (!isset ($_GET ['getpassword']) && !isset ($_GET ['changepassword'])) return true;
		else return false;
	}
	
	/**
	 *	isGetPassword ()
	 *	Active when getpassword is set
	 */
	public function isGetPassword () {
		if (isset ($_GET ['getpassword'])) return true;
		else return false;
	}
	
	/**
	 *	isChangePassword ()
	 *	Active when changepassword is set
	 */
	public function isChangePassword () {
		if (isset ($_GET ['changepassword'])) return true;
		else return false;
	}
	
	/**
	 *	displayMessages ()
	 *	Display the output messages after the operation has been performed sucessfully
	 */
	public function displayMessages () {
		foreach ($this->messages as $message)
			print "<p>".$message."</p>";
	}
	
	/**
	 *	showErrors ()
	 *	Display some potential erros
	 */
	public function showErrors () {
		if (sizeof ($this->errors) > 0) {
			foreach ($this->errors as $error)
				print "<p>".$error."</p>";
		}
	}
	
	/**
	 *	getData ()
	 *	Get the data sent
	 */
	private function getData () {
		$this->firstname = $this->cleanInput ($_POST ['first_name']);
		$this->lastname = $this->cleanInput ($_POST ['last_name']);
		$this->bu = $_POST ['bu'];
		$this->email = $this->cleanInput ($_POST ['email']);
		$this->password = $this->cleanInput ($_POST ['password']);
		$this->confirm_password = $this->cleanInput ($_POST ['confirm_password']);
	}
	
	/**
	 *	cleanInput ()
	 *	Clean an input
	 */
	private function cleanInput ($s) {
		// remove \ in name
		$s = stripslashes ($s);
		
		// remove spaces at the end and at the beginning
		$s = preg_replace ("/^[ ]+/i", "", $s);
		$s = preg_replace ("/[ ]+$/i", "", $s);
		
		return $s;
	}
	
	/**
	 *	checkData ()
	 *	Check the data sent
	 */
	private function checkData () {
		// Then check that the 2 passwords are the same
		if (!$this->checkPasswordCohesion  ())
			array_push ($this->errors, "The 2 passwords do not match. Make sure they are identical.");
			
		// Then check that the 2 passwords are the same
		if (!$this->checkpasswordUnicity  ())
			array_push ($this->errors, "The firstname or lastname appears in the password. Please make sure the password does not include the firstname or lastname.");
		
		// Then check that the password is good length
		if (!$this->checkPasswordLength  ())
			array_push ($this->errors, "The password does not respect the security policy. Your password must be at least 8 characters long and contain a combination of uppercase and lowercase letters and numerals.");
		
		// Check that all the fields are filled in and the password is valid
		if (!$this->checkAllFields  ())
			array_push ($this->errors, "Some fields are not filled in or the email entered is incorrect.");
		
		
		if (sizeof ($this->errors) > 0)
			return false;
		else 
			return true;
	}
	
	/**
	 *	checkPasswordCohesion ()
	 *	Check that the 2 passwords are the same
	 */
	private function checkPasswordCohesion () {
		if ($this->password != $this->confirm_password) return false;
		return true;
	}
	
	/**
	 *	checkpasswordUnicity ()
	 *	Check that the firstname or lastname is not in the password
	 */
	private function checkpasswordUnicity () {
		if (preg_match ("/(".$this->firstname.")/i", $this->password)) return false;
		if (preg_match ("/(".$this->lastname.")/i", $this->password)) return false;
		return true;
	}
	
	/**
	 *	checkpasswordUnicityFromSma ()
	 *	Check that the firstname or lastname is not in the password FROM THE SMA
	 */
	private function checkpasswordUnicityFromSma ($sma) {
		$parts = explode ('.', $sma);
		foreach ($parts as $part) {
			if (preg_match ("/(".$part.")/i", $this->password)) return false;
		}
		return true;
	}
	
	/**
	 *	checkPasswordLength ()
	 *	Check that the password is valid with the AD rules
	 */
	private function checkPasswordLength () {
		// Check minimum length
		if (strlen ($this->password) < 8) return false;
		
		// Check that we have at least one letter in lowercase.
		if (!preg_match ("/[a-z]+/", $this->password)) return false;
		
		// Check that we have at least one letter in Uppercase.
		if (!preg_match ("/[A-Z]+/", $this->password)) return false;
		
		// Check that we have at least one number
		if (!preg_match ("/[0-9]+/", $this->password)) return false;
		return true;
	}
	
	/**
	 *	checkAllFields ()
	 */
	private function checkAllFields () {
		$ok = true;
		
		if (empty ($_POST ['first_name'])) {
			array_push ($this->errors, "Field First Name is empty.");
			$ok = false;
		}
		if (empty ($_POST ['last_name'])) {
			array_push ($this->errors, "Field Last Name is empty.");
			$ok = false;
		}
		if (empty ($_POST ['email'])) {
			array_push ($this->errors, "Field  E-mail is empty.");
			$ok = false;
		}
		if ($_POST ['bu'] == "Select -----------------------------------") {
			array_push ($this->errors, "Field Business Unit is empty.");
			$ok = false;
		}
		if (empty ($_POST ['first_name'])) {
			array_push ($this->errors, "Field First Name is empty.");
			$ok = false;
		}
		
		return $ok;
	}
	
	
	/**
	 *	isCreating ()
	 *	Return true if in creation process
	 */
	public function isCreating () {
		return $this->is_creating;
	}
	
	/**
	 *	createUserName ()
	 *	Create an user name from the first/last name
	 */
	private function createUserName () {
		// Replace the spaces by -
		$firstname = preg_replace ("/[ ]+/i", "-", $this->firstname);
		$lastname = preg_replace ("/[ ]+/i", "-", $this->lastname);

		// replace the the ' by -
		$firstname = preg_replace ("/[\']+/i", "-", $firstname);
		$lastname = preg_replace ("/[\']+/i", "-", $lastname);

		// Concat the 2
		$username = strtolower ($firstname.".".$lastname);
		
		// Cut it if too long
		if (strlen ($username) > $this->conf->getv ('MAX_USERNAME_LENGTH', 20))
			$username = substr ($username, 0, $this->conf->getv ('MAX_USERNAME_LENGTH', 20));
		
		return $username;
	}
	
	/**
	 *	createUser ()
	 *	Create a new user
	 */
	public function createUser () {
		$ldap = $this->createLdap ();
		
		$sma = $this->createUserName ();
		
		/**
		 *	Check that the user is not already in the base, (NOT in the country folder so we make sure we don't have doublons)
		 */
		if ($ldap->userExistsBySMA ($sma) || !$this->userIsUnique (&$ldap)) {
			array_push ($this->errors, "This user already exists."); 
			
			/**
			 *	Send the logins by email
			 *	TO FINISH
			 */
			if ( ($email = $ldap->getUserEmail ($this->firstname." ".$this->lastname, $this->bu)) != NULL) {
				//$this->sendLoginsByEmail ($email, $this->bu);
				//array_push ($this->errors, "An email has been sent to ".$email." with the user's login details.");
			}
			return false; 
		}
	

		/**
		 *	Now create it !
		 */
		if ($ldap->add ($sma, $this->password, $this->firstname, $this->lastname, $this->bu, $this->email)) {
			/**
			 *	Now display the logins and send them by email
			 */
			array_push ($this->messages, "The user has been created.</p><p><b>Login:</b> ".
							$this->conf->get ('domain').$sma."<br><b>Password: </b>".$this->password."</p>
				<p>You now need to ask your site manager to grant you rights within the section you wish to access.<br>
				You'll then be able to log into Sharepoint.</p>");
				
			//Send the logins by email
			$this->sendLogins ("MA&#92;".$sma, $this->password, $this->email);
			
			//Add it to the database
			$this->addUserToDb ($sma, $this->firstname, $this->lastname, $this->email, $this->bu, $this->password);
			
			//Adn alert the admins
			$this->alertAdmins ("MA&#92;".$sma, $this->bu, $this->email);
		}
		
		return true;
	}
	
	/**
	 *	userIsUnique ()
	 *	Check if the user doesn't already have an account FROM THE DB, by looking to his firstname+lastname and by his email
	 */
	private function userIsUnique (&$ldap) {
		if (!$this->dbLayer) $this->initDb ();
		
		// Check email unicity in DB among all emails
		foreach ($this->dbLayer->getEmails () as $email) {
			if ($this->email == $email) return false;
		}
		
		// Check email in LDAP among all emails!!
		//if ($this->email == $ldap->getUserEmail ($this->firstname." ".$this->lastname)) return false;
		
		// Check firstname-lastname unicity
		//en fait pas possible, pusique si le sma est pass� c'est que le pr�nom ou nom a chang�, docn relou!
		
		return true;
	}
	
	/**
	 *	addUserToDb ()
	 *	Add an user to the database
	 */
	public function addUserToDb ($sma, $firstname, $lastname, $email, $bu, $password) {
		if (!$this->dbLayer) $this->initDb ();
		
		if (($this->dbLayer->addUserToDb ($sma, $firstname, $lastname, $email, $bu, $password)) !== FALSE) {
			return true;
		} else
			return false;
	}
	
	/**
	 *	getPassword ()
	 *	Send the password by email
	 */
	public function getPassword () {
		// Check first
		if (empty ($this->email)) {
			array_push ($this->errors, "The entered e-mail is invalid."); 
			return false;
		}
		
		// Now go get it!!
		if (!$this->dbLayer) $this->initDb ();
		if (($user = $this->dbLayer->getPassword ($this->email)) !== FALSE) {
			
			$this->sendPasswordByEmail ("MA&#92;".$user ['sma'], $user ['password'], $this->email);
			
			array_push ($this->messages, "Your password has been sent to ".$this->email); 
		} else 
			array_push ($this->messages, "No existing user has been found for the e-mail ".$this->email.". <br><br>Please
							use the Create my account page to create a new account or ask your logins to the administrators
							if you believe you already have an account."); 
			
		return true;
	}
	
	/**
	 *	changePassword ()
	 *	Change the password
	 *	So we need to:
	*		- check that the account for this login exists and get it
	*		- check that the old password is correct
	*		- change the password in the AD + in mySQL
	 */
	public function changePassword () {
		// Get the account parameters form mySQL (cause we need the old password)
		if (!$this->dbLayer) $this->initDb ();
		// create an LDAP instance
		$ldap = $this->createLdap ();
		
		/**
		 *	Remove the MA\ from the login
		 */
		$sma = preg_replace ("@^(".$this->conf->get ('domain')."\\)@i", "", $this->login);
		//echo "SMA: $sma - login: ".$this->login."<br>";
		
		if (($user = $this->dbLayer->getUserBySma ($sma)) !== FALSE) {
			// Check the old password
			if ($user ['password'] != $this->old_password) {
				array_push ($this->errors, "Old password is not correct.");
				return false;
			}
			
			// Ok, now let's change it, but before check that the 2 new passwords match!
			if (!$this->checkPasswordCohesion ()) {
				array_push ($this->errors, "The 2 new passwords do not match.");
				return false;
			}
			
			// Then check that the 2 passwords are the same
			if (!$this->checkpasswordUnicityFromSma  ($sma)) {
				array_push ($this->errors, "The firstname or lastname appears in the password. Please make sure the password does not include the firstname or lastname.");
				return false;
			}
			
			// Then check that the password is good length
			if (!$this->checkPasswordLength  ()) {
				array_push ($this->errors, "The password does not respect the security policy. Your password must be at least 8 characters long and contain a combination of uppercase and lowercase letters and numerals.");
				return false;
			}
			
			// Assign the BU
			$this->bu = $user ['bu'];
			
			/**
			 *	For hand created accounts we have a bit of a situation.
			 *	The SMA will be Firstname Name, whereas users created by this app will be firstname.lastname
			 *	To  keep managing them, we check if the user exists as firstname.sma, if not we'll try the other option.
			 */
			if (!$ldap->getUserBySma ($sma)) {
				// firstname.lastname not found, we'll try the Firstname Lastname way
				$ids = explode (".", $sma);
				$new_sma = ucfirst ($ids [0]).' '.ucfirst ($ids [1]);
				echo "New SMA is $ad_sma<br>";
				
				if (!$ldap->getUserBySma ($new_sma)) {
					array_push ($this->errors, "Cannot find user $sma neither $new_sma in the AD.");
					return false;
				} else
					$ad_sma = $new_sma;	// we'll use this one instead
			} else {
				// user found -> the sma is firstname.lastname
				$ad_sma = $sma;
			}
			
			//echo "SMA change password: $sma, ad_sma: $ad_sma<br>";

			// and change
			$this->goChangePassword ($sma, $ad_sma, $this->password);
		} else {
			array_push ($this->errors, "Cannot find user $sma.");
			return false;
		}
		return true;
	}
	
	/**
	 *	goChangePassword ()
	 *	Do the change, must have been verified before
	 *	@param {String} sma : le SMA qui est dans la base
	 *	@param {String} ad_sma : le SMA de l'ad (dc michel patou pr anciens, michel.patou pr nouveaux)
	 *	@param {String} password
	 */
	private function goChangePassword ($sma, $ad_sma, $new_password) {
		$ldap = $this->createLdap ();
		
		if ($ldap->changePassword ($ad_sma, $new_password, $this->bu)) {
			/**
			 *	Now update the MySQL paswword
			 */
			if (!$this->dbLayer) $this->initDb ();
			$this->dbLayer->updatePassword ($sma, $new_password);
			
			array_push ($this->messages, "The password has been changed.");
			return true;
			
		} else {
			array_push ($this->errors, "Change password failed!");
			return false;
		}
	}
	
	/**
	 *	sendLoginsByEmail ()
	 *	Send the logins by email
	 */
	public function sendLoginsByEmail ($email, $country) {
		$ldap = $this->createLdap ();
		
		/**
		 *	Get the user's details
		 */
		$people = $ldap->getUserByEmail ($email, $country);
		//print_r ($people);
		
		/**
		 *	Now send the login (MA\xx.yy) + password
		 *	@note The password is not taken from the AD (impossible) but from our list of users
		 */
		$login = $this->conf->get ('domain').$people ['samaccountname'] [0];
		$password = $this->getPasswordBySMA ($people ['samaccountname']);
		
		array_push ($this->errors, "Login: ".$login." with password: $password<br>");
		
		//$this->sendLogins (...);
	}
	
	/**
	 *	getPasswordBySMA ()
	 *	Get the password of an user by its SMA
	 *	@param {String} sma
	 */
	public function getPasswordBySMA ($sma) {
		// TO DO
		return "unknown";
	}
	
	
	/**
	 *	sendLogins ()
	 */
	private function sendLogins ($login, $password, $email) {
		$subject = "Your Sharepoint logins";
		$body = $this->htmlEmail ("<p>Welcome to Sharepoint,</p>
								<p><b>Your login:</b> ".$login."<br>
								<b>Your password: </b>".$password."</p>
				You will now be able to log to Sharepoint into the sites from which you have access.<br>
				Please contact your Sharepoint Site Manager for the next steps.</p>
				");
				
		$from = $this->conf->get ('sender', 'email');
		$fromName = "Sharepoint";
		$receiver = $email;
		
		mail::send ($subject, $body, $from, $fromName, $receiver, 'HTML');
	}
	
	/**
	 *	alertAdmins ()
	 */
	private function alertAdmins ($login, $bu, $email) {
		$subject = "New Sharepoint account created!";
		$body = $this->htmlEmail ("<p>Dear admins,</p>
								<p>A new user has created an account using Sharepoint Pass.</p>
								<p><b>Login of this new user:</b> ".$login."<br>
								<b>BU: </b>".$bu."<br>
								<b>E-mail: </b>".$email."
				</p>");
				
		$from = $this->conf->get ('sender', 'email');
		$fromName = "Sharepoint Pass";
		$receiver = $this->conf->get ('admin', 'email');
		
		mail::send ($subject, $body, $from, $fromName, $receiver, 'HTML');
	}
	
	/**
	 *	sendPasswordByEmail ()
	 */
	private function sendPasswordByEmail ($login, $password, $email) {
		$subject = "Your Sharepoint logins";
		$body = $this->htmlEmail ("<p>Dear Sharepoint user,</p>
								<p>As requested, here are your login details to access to Sharepoint.</p>
								<p><b>Your login:</b> ".$login."<br>
								<b>Your password: </b>".$password."</p>
				");
				
		$from = $this->conf->get ('sender', 'email');
		$fromName = "Sharepoint";
		$receiver = $email;
		
		mail::send ($subject, $body, $from, $fromName, $receiver, 'HTML');
	}
	
	/**
	 *	htmlEmail ()
	 *	Apply an HTML template to the email
	 *	@return {String} the formatted content
	 */
	private function htmlEmail ($content) {
		$template = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">
		<html><head><title>Sharepoint</title><meta http-equiv=\"Content-Type\" content=\"text/html; charset=iso-8859-1\">
		<style type=\"text/css\">
		body { background-color: #fff; color: #000; font-family: Arial, sans-serif; font-size: 13px; }
		a { font-size: 12px; color: #ff6600; font-style: normal;  text-decoration: none; }
		a:visited { color: #ff6600; }
		a:hover { text-decoration: underline; }
		p { font-weight: normal; font-size: 12px; color: #000; padding-top: 2px; margin: 1px; margin-bottom: 12px;  }
		h3 { color: #fe0000; font-size: 12px; margin-bottom: 3px; }
		</style>
		</head>
		<body>
		 <h3>Sharepoint logins</h3>".
		 $content
		 ."</body></html>";
		 
		 return $template;
	}
	
	/**
	 *	createLdap ()
	 *	Create a new LDAP instance
	 */
	private function createLdap () {
		$ldap = new ldapLayer ();
		if (!$ldap->connect ($this->conf->get ('ldap_server', 'ldap'), 
								$this->conf->get ('ldap_login', 'ldap'), 
								$this->conf->get ('ldap_password', 'ldap'))) {
			array_push ($this->errors, "Can't connect to the Sharepoint server.");
			return false;
		}
		return $ldap;
	}
}
?>