<?php
/**
 *	lib.mail
 *	Based on external lib PHPMailer
 */

class mail {
	/**
	 *	current_mail
	 *	Contains the current mail if one has been created
	 */
	var $current_mail = NULL;
	
	/**
	 *	createMail ()
	 *	Create an e-mail
	 */
	function createMail () {
	
	}
	
	
	/**
	 *	send ()
	 *	Send an e-mail.
	 *	Use it as mail::send (...);
	 *
	 *	@param {Array} attachments : some attachments. HAS TO BE AN ARRAY!
	 *	@note: if you use some attachments, they have to be stored on local. If you do upload them from a form
	 *	and doesn't need it anymore then don't forget to unlink() it after you sent the email.
	 */
	function send ($subject, $body, $from, $fromName, $receiver, $format='HTML', $attachments=NULL) { 
		$mail = new PHPmailer ();
		$mail->IsSMTP ();
		if ($format == 'HTML') $mail->IsHTML (true);
		$mail->Host = "172.30.47.11";		//a gerer dans la conf en lib
		$mail->From = $from;
		$mail->FromName   = $fromName;
		
		/**
		 *	Add multiple addresses, may not be necessary
		 */
		if (preg_match ("/;/", $receiver)) {
			$addresses = explode (";", $receiver);
			foreach ($addresses as $addr) {
				$mail->AddAddress ($addr);
			}
		} else
			$mail->AddAddress ($receiver);
			
		$mail->AddReplyTo ($from);	
		$mail->Subject = $subject;
		$body = eregi_replace("[\]", '', $body);
		if ($format == 'HTML') $mail->MsgHTML ($body);
		else $mail->Body = $body;
		
		if ($attachments && is_array ($attachments)) {
			foreach ($attachments as $key=>$value) { //loop the Attachments to be added …
				$mail->AddAttachment (base::getPath ($value));
			}
		}

		if(!$mail->Send ()){ //Teste si le return code est ok.
			$mail->SmtpClose ();
			$errorMsg = $mail->ErrorInfo;
			unset ($mail);
			echo "Error while sending mail: ".$errorMsg;
			return false;
			//return $this->_error (2, $errorMsg);
		} 
		
		/**
		 *	Put this mail in the right sent folder, according to the user
		 *	A CHANGER PR PRENDRE EN COMPTE L'USER EN UTILISANT UN EVENT USER
		 */

		/**
		 *	Put this mail in the right sent folder, according to the user
		 *	A CHANGER PR PRENDRE EN COMPTE L'USER EN UTILISANT UN EVENT USER
		 */
		//$this->fs->archive->email ('web', 'sent', $subject, $body, $from, $receiver, $format, $attachments);

		return true;
	}

	/**
	 *	archiveEmail ()
	 *	Archive the email on the disk
	 *	DEPRECATED
	 */
/*	private function archiveEmail ($user, $type, $subject, $body, $from, $receiver, $format, $attachments) {
		$txt = "Subject: ".$subject."\nFrom: ".$from."\nTo: ".$receiver."\nBody: ".$body;
		//Rajouter la date et formaliser le nom, quoique c'est le fs qui le fait
		$this->fs->writeNew ("%root/users/".$user."/inbox/".$type."/".$subject, $txt);
	}*/
}
?>