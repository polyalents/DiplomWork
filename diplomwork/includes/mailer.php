<?php
if (!defined('INDEX_LOAD')) {
	// не позволяем запускать этот файл напрямую
	die();
}
/**
 * Класс для отправки почты
 * Class mail
 */
class mailer {
	// Maximum execution time of 60 seconds exceeded
	function send_email($to, $msg, $subject, $filepath = false) {
		$ajax = new ajax();
		
//		set_time_limit(10);
		
		//SMTP needs accurate times, and the PHP time zone MUST be set
		//This should be done in your php.ini, but this is how to do it if you don't have access to that
		
		//Create a new PHPMailer instance
		$mail = new PHPMailer\PHPMailer\PHPMailer;
		
		$mail->CharSet = 'utf-8';
		//Tell PHPMailer to use SMTP
		$mail->isSMTP();
//		$mail->SMTPOptions = array(
//			'ssl' => array(
//				'verify_peer' => false,
//				'verify_peer_name' => false,
//				'allow_self_signed' => true
//			)
//		);
		//Enable SMTP debugging
		// 0 = off (for production use)
		// 1 = client messages
		// 2 = client and server messages
		$mail->SMTPDebug = 0;
//		$mail->Timeout = 10;
		//Ask for HTML-friendly debug output
		$mail->Debugoutput = 'html';
		//Set the hostname of the mail server
		$mail->Host = SENDER_EMAIL['smtp_host'];
		//Set the SMTP port number - likely to be 25, 465 or 587
		$mail->Port = SENDER_EMAIL['smtp_port'];
		//Whether to use SMTP authentication
		$mail->SMTPAuth = true;
		//Username to use for SMTP authentication
		$mail->Username = SENDER_EMAIL['smtp_login'];
		//Password to use for SMTP authentication
		$mail->Password = SENDER_EMAIL['smtp_password'];
		//Set who the message is to be sent from
		try {
			$mail->setFrom(SENDER_EMAIL['smtp_login'], SENDER_EMAIL['name']);
		} catch(Exception $e) {
			$ajax->error(var_export($e, true));
		}
		//Set who the message is to be sent to
		try {
			$mail->addAddress($to);
		} catch(Exception $e) {
			$ajax->error(var_export($e, true));
		}
		//Set the subject line
		$mail->Subject = $subject;
		//Read an HTML message body from an external file, convert referenced images to embedded,
		//convert HTML into a basic plain-text alternative body
		try {
			$mail->msgHTML($msg);
		} catch(Exception $e) {
			$ajax->error(var_export($e, true));
		}
		//Replace the plain text body with one created manually
		//$mail->AltBody = strip_tags($html);
		//Attach an image file
		if (is_array($filepath)) {
			foreach ($filepath as $v) {
				try {
					$mail->addAttachment($v);
				} catch(Exception $e) {
					$ajax->error(var_export($e, true));
				}
			}
		} else {
			if ($filepath) {
				try {
					$mail->addAttachment($filepath);
				} catch(Exception $e) {
					$ajax->error(var_export($e, true));
				}
			}
		}
		
		//send the message, check for errors
		try {
			$success = $mail->send();
		} catch(Exception $e) {
			$ajax->error(var_export($e, true));
			die;
		}
		
		$mail->SmtpClose();
		
		if ($success) {
			return array('error' => 0);
		} else {
			$ajax->error('Не удалось отправить письмо администратору.');
			die;
		}
	}
}