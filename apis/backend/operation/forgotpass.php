<?php
	session_start();
	require('./../util/functions.php');
	require_once ('./../util/hashing.php');
	$utility = new Utility();
	$fields = array('email');

	$error = false; 
	foreach($fields AS $fieldname) { 
	  if(!isset($_POST[$fieldname]) || empty($_POST[$fieldname])) {
	    echo 'Field '.$fieldname.' missing!<br />';
	    $error = true;
	  }
	}

	if(!$error) {
		$param = $utility->clean_input($_POST['email']);
		$sql = "SELECT * FROM customers WHERE email = :email";
		$check = $utility->exists_by_id($param);
		$auth = new Auth;
		$token = $auth->random_char();
		if ($check !== NULL) {
			$send_verify = new Mailing();
			if($send_verify->passrecover($param, $token)){
				$utility->insert('passrecovery', array('email', 'userid', 'recovery_code'), array($param, $_SESSION['user_id'], $token));
					$_SESSION['message'] = "Check Your Mail To Reset Your Password";
					$_SESSION['messagetype'] ="alert alert-success";
					$utility->redirect('./../../password/reset.php');
			}else{
				// echo "error sending mail";
				$_SESSION['message'] = "error sending mail";
				$_SESSION['messagetype'] ="alert alert-danger";
				$utility->redirect('./../../password/reset.php');
			}
		}else{
			// echo "user not registered";
			$_SESSION['message'] = "user not registered";
			$_SESSION['messagetype'] ="alert alert-danger";
			$utility->redirect('./../../password/reset.php');
		}

	}else{
		echo "input error";
		$_SESSION['message'] = "input error";
		$_SESSION['messagetype'] ="alert alert-danger";
		$utility->redirect('./../../password/reset.php');
	}
?>