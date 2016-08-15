<?php
require_once('config.php');

class MailSendSmtp {

public $oMailData;
public $oMailAuth;

public function _construct () {

  //Initialize needed variables
  $this->oMailAuth['auth_name'] = 'David Stoops';
  $this->oMailAuth['auth_account'] = 'email@thisdomain.co.uk';
  $this->oMailAuth['auth_password'] = 'th1Sp6ssW0rd';
 
  // Assumption based on this being submitted through a form to send mail class using field name, purchase cost, email, message, date, and reason for contact
  // Sanitize fields for security checks
  $_POST['name'] = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
  $_POST['purchase_cost'] = filter_var($_POST['purchase_cost'], FILTER_SANITIZE_NUMBER_INT);
  $_POST['email'] = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
  $_POST['message'] = filter_var($_POST['message'], FILTER_SANITIZE_SPECIAL_CHARS);
  $_POST['date'] = filter_var($_POST['date'], FILTER_SANITIZE_STRING);
  $_POST['reason_for_contact'] = filter_var($_POST['reason_for_contact'], FILTER_SANITIZE_STRING);
  
  // add additionals for reply, bounce, and source
  $_POST[['reply-to'] = 'noreply@thisdomain.co.uk';
  $_POST[['from'] = 'source@thisdomain.co.uk';
  
  // store post data to variable
  $this->oMailData = array(
        'From' => $_POST['from'],
        'To' => $_POST['email'],
        'Subject' => $_POST['reason_for_contact'] . ' - ' . $_POST['date'],
        'HtmlBody' => $_POST['content']
        'ReplyTo' => $_POST['reply_to']
    )
  
  $sendEmail = 0;
  if (isset(API_MAIL_SEND_KEY)) {
      $sendEmail = $this->postmark($this->oMailData);
  } else {
      $sendEmail = $this->send($this->oMailData);
  }

	  if(isset($sendEmail) && $sendEmail == 200) {
	    header('Location: /thank-you');
	  } else {
	    print "<strong><p style='color: #ff0000'>Email failed to send, please try again.</p></strong>";
	  }

}

// standard email using zend
public function send($email,$auth) {

 //smtp config options for zend mail
 $smtpHost = 'smtp.gmail.com';
 $smtpConf = array(
  'auth' => 'login',
  'ssl' => 'ssl',
  'port' => '465',
  'username' => $auth['auth_account'],
  'password' => $auth['auth_password']
 );
 $transport = new Zend_Mail_Transport_Smtp($smtpHost, $smtpConf);

 //Create email
 $mail = new Zend_Mail();
 $mail->setFrom($auth['auth_account'], $auth['auth_name']);
 $mail->addTo($email['to']);
 $mail->setSubject($email['Subject']);
 $mail->setBodyText($email['HTMLBody']);

 //Send
 $sent = 200;
 try {
  $mail->send($transport);
 }
 catch (Exception $e) {
  $sent = 500;
 }

 //Return boolean indicating success or failure
 return $sent;

}

// postmark send via curl, standard initialise and send using api
// postmark could be switch with sendgrid by swapping api key, and smtp api reference.
public function postmark($email){
		
    $json = json_encode(array($email);
	
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://api.postmarkapp.com/email');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Content-Type: application/json',
        'X-Postmark-Server-Token: ' . API_MAIL_SEND_KEY
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    $response = json_decode(curl_exec($ch), true);
    $sent = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $sent === 200;
	
	}
	
}