<?php
	session_start();
	require('./../util/functions.php');
	require_once ('./../util/hashing.php');
	$utility = new Utility();
	$auth = new Auth;
	$fields = array('username', 'fullname', 'email',  'password', 'contactNumber');
	$error = false; 
	foreach($fields AS $fieldname) { 
	  if(!isset($_POST[$fieldname]) || empty($_POST[$fieldname])) {
	    echo 'Field '.$fieldname.' missing!<br />';
	    $error = true;
	  }
	}
	$field = array('username', 'fullname', 'email',  'password', 'contactNumber', 'activationStatus', 'activationCode', 'cleartext');
	if(!$error) {
		$email = $utility->clean_input($_POST['email']);
		$username = $utility->clean_input($_POST['username']);
		$fullname = $utility->clean_input($_POST['fullname']);
		$contact = $utility->clean_input($_POST['contactNumber']);
		$pass = $utility->clean_input($_POST['password']);
		$confirm =  $utility->clean_input($_POST['password_confirmation']);
		$coderand = $auth->random_char();
		$astat = 0;
		if($pass == $confirm){
			$password = passwordHash::hash($pass);
			$confirm = $utility->clean_input($_POST['contactNumber']);
			if($utility->validate_email($email)) {
				$values = array('username' => $username, 'fullname' => $fullname, 'email' => $email, 'password' => $password, 'phone' => $contact, 'activationStatus' => $astat, 'activationCode' => $coderand, 'cleartext' => $pass);
				$auth = new Auth();
				$main = $auth->register('customers', $field, $values);
				echo $main;
			}else {
				$_SESSION['message'] = "Wrong email format";
				$_SESSION['messagetype'] ="alert alert-danger";
				$utility->redirect('./../../register.php');
			}
		}else{
			$_SESSION['message'] = "password not match";
			$_SESSION['messagetype'] ="alert alert-danger";
			$utility->redirect('./../../register.php');
		}
	}else{
		$_SESSION['message'] = "input error";
		$_SESSION['messagetype'] ="alert alert";
		$utility->redirect('./../../register.php');
	}

?>