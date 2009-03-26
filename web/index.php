<?php
include ('../core/class.adManager.php');
$go = new adManager ();

$country = (isset ($_POST ['bu'])) ? $_POST ['bu'] : '';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
<title>Sharepoint Logins</title>
<link rel="stylesheet" type="text/css" href="css/screen.css?v=1.2" />
</head>

<body>

  <div id="content">
    <div id="top-bar">
	  <h2>Sharepoint</h2>
	</div>
	
	<div id="menu">
	  <ul>
		<li><a href="?create">Create my account</a></li>
		<li><a href="?getpassword">Get my password</a></li>
		<li><a href="?changepassword">Change my password</a></li>
	  </ul>
	</div>
	
	<div id="in">
		<?php if ($go->isCreateAccount ()): ?>

			<?php if (!$go->isCreating ()): ?>
			
			<h2>Create a new account:</h2>
			
			<div id="errors">
				<?php echo $go->showErrors (); ?>
			</div>
			
			<form name="createlogins" method="post" action="">
			  <table border="0">
				<tr><td width="200">First name:</td><td><input type="text" size="30" name="first_name" value="<?php echo $_POST ['first_name']; ?>" /></td><td width= "20" class="mandat">*</td></tr>
				<tr><td>Last name:</td><td><input type="text" size="30" name="last_name" value="<?php echo $_POST ['last_name']; ?>" /></td><td class="mandat">*</td></tr>
				<tr><td>E-mail:</td><td><input type="text" size="30" name="email" value="<?php echo $_POST ['email']; ?>" /></td><td class="mandat">*</td></tr>
				<tr><td>Business Unit:</td><td>
					<select name="bu">
						<option name="none"> Select  -----------------------------------</option>
						<option name="Australia"<?php if ($country == "Australia") echo " selected"; ?>>Australia</option>
						<option name="Austria"<?php if ($country == "Austria") echo " selected" ?>>Austria</option>
						<option name="Belgium"<?php if ($country== "Belgium") echo " selected" ?>>Belgium</option>
						<option name="Brazil"<?php if ($country == "Brazil") echo " selected" ?>>Brazil</option>
						<option name="Canada"<?php if ($country == "Canada") echo " selected" ?>>Canada</option>
						<option name="China"<?php if ($country == "China") echo " selected" ?>>China</option>
						<option name="Czech Republic"<?php if ($country == "Czech Republic") echo " selected" ?>>Czech Republic</option>
						<option name="France"<?php if ($country == "France") echo " selected" ?>>France</option>
						<option name="Germany"<?php if ($country == "Germany") echo " selected" ?>>Germany</option>
						<option name="Germany (Elvia)"<?php if ($country == "Germany (Elvia)") echo " selected" ?>>Germany (Elvia)</option>
						<option name="Greece"<?php if ($country == "Greece") echo " selected" ?>>Greece</option>
						<option name="GTS"<?php if ($country == "GTS") echo " selected" ?>>GTS</option>
						<option name="HQ"<?php if ($country == "HQ") echo " selected" ?>>HQ</option>
						<option name="India"<?php if ($country == "India") echo " selected" ?>>India</option>
						<option name="Ireland"<?php if ($country == "Ireland") echo " selected" ?>>Ireland</option>
						<option name="Italy"<?php if ($country == "Italy") echo " selected" ?>>Italy</option>
						<option name="Japan"<?php if ($country == "Japan") echo " selected" ?>>Japan</option>
						<option name="Mexico"<?php if ($country == "Mexico") echo " selected" ?>>Mexico</option>
						<option name="Netherlands"<?php if ($country == "Netherlands") echo " selected" ?>>Netherlands</option>
						<option name="Poland"<?php if ($country == "Poland") echo " selected" ?>>Poland</option>
						<option name="Portugal"<?php if ($country == "Portugal") echo " selected" ?>>Portugal</option>
						<option name="Reunion"<?php if ($country == "Reunion") echo " selected" ?>>Reunion</option>
						<option name="Russia"<?php if ($country == "Russia") echo " selected" ?>>Russia</option>
						<option name="Singapore"<?php if ($country == "Singapore") echo " selected" ?>>Singapore</option>
						<option name="Spain"<?php if ($country == "Spain") echo " selected" ?>>Spain</option>
						<option name="Switzerland"<?php if ($country == "Switzerland") echo " selected" ?>>Switzerland</option>
						<option name="Switzerland (Medi 24)"<?php if ($country == "Switzerland (Medi 24)") echo " selected" ?>>Switzerland (Medi 24)</option>
						<option name="Thailand"<?php if ($country == "Thailand") echo " selected" ?>>Thailand</option>
						<option name="Turkey"<?php if ($country == "Turkey") echo " selected" ?>>Turkey</option>
						<option name="United Kingdom"<?php if ($country == "United Kingdom") echo " selected" ?>>United Kingdom</option>
						<option name="United States"<?php if ($country == "United States") echo " selected" ?>>United States</option>
					</select>
				</td><td class="mandat">*</td></tr>
				<tr><td>Password:</td><td><input type="password" size="30" name="password" /></td><td class="mandat">*</td></tr>
				<tr><td>Confirm your password:</td><td><input type="password" size="30" name="confirm_password" /></td><td class="mandat">*</td></tr>
				<tr><td colspan="3" align="center"><input type="submit" name="gocreate" value="Create your account" /></td></tr>
			   </table>
			</form>
			
			<br>
			<p style="font-size: 11px;"><i>Note:</i> Your password must be at least 8 characters, include at least one letter in lowercase, one letter in 
			uppercase and have at least one number. Also, your firstname and lastname cannot figure within your password.</p>

			<?php else: ?>
			  <div id="messages">
				<?php $go->displayMessages (); ?>
			  </div>
			<?php endif; ?>
		
		<?php elseif ($go->isGetPassword ()): ?>
		
			<?php if (!$go->isCreating ()): ?>
			
			<h2>Get my password:</h2>
			
			<div id="errors">
				<?php echo $go->showErrors (); ?>
			</div>
			
				<form name="getpassword" method="post" action="">
				  <table border="0" width="100%">
				    <tr>
					  <td>Your e-mail address:</td>
					  <td><input type="text=" size="30" name="email"  value="<?php echo $_POST ['email']; ?>" /></td>
					</tr>
					<tr>
					  <td colspan="2" align="center">
					    <input type="submit" name="gogetpassword" value="Get my password" />
					  </td>
					</tr>
				  </table>
				</form>
			<?php else: ?>
			  <div id="messages">
				<?php $go->displayMessages (); ?>
			  </div>
			<?php endif; ?>
			
		<?php elseif ($go->isChangePassword ()): ?>
			
			<?php if (!$go->isCreating ()): ?>
			
			<h2>Change my password:</h2>
			
			<div id="errors">
				<?php echo $go->showErrors (); ?>
			</div>
			
				<form name="changepassword" method="post" action="">
				  <table border="0" width="100%">
				    <tr>
					  <td>Your login (ex: MA\john.doe):</td>
					  <td><input type="text" size="30" name="login"  value="<?php echo stripslashes ($_POST ['login']); ?>" /></td>
					</tr>
					<tr>
					  <td>Your old password:</td>
					  <td><input type="password" size="30" name="oldpassword"  value="<?php echo $_POST ['oldpassword']; ?>" /></td>
					</tr>
					<tr>
					  <td>Your new password:</td>
					  <td><input type="password" size="30" name="password"  value="<?php echo $_POST ['password']; ?>" /></td>
					</tr>
					<tr>
					  <td>Your new password (confirm):</td>
					  <td><input type="password" size="30" name="confirm_password"  value="<?php echo $_POST ['confirm_password']; ?>" /></td>
					</tr>
					<tr>
					  <td colspan="2" align="center">
					    <input type="submit" name="gochangepassword" value="Change my password" />
					  </td>
					</tr>
				  </table>
				</form>
				
				<br>
				<p style="font-size: 11px;"><i>Note:</i> Your password must be at least 8 characters, include at least one letter in lowercase, one letter in 
				uppercase and have at least one number. Also, your firstname and lastname cannot figure within your password.</p>
				
			<?php else: ?>
			  <div id="messages">
				<?php $go->displayMessages (); ?>
			  </div>
			<?php endif; ?>
			
		<?php endif; ?>
		
		<p><br>For more details about Sharepoint, go to the <a href='http://magellan.mondial-assistance.com/informationtechnologies/businesssolutions/sharepoint/'>Sharepoint Magellan page.</a></p>
	  </div>
	  
	  <div id="footer">
		<p>AD Account Manager 1.0.3.0</p>
	  </div>
	  
  </div>
  
</body>
</html>