<?php
	session_start();
	require('./../util/functions.php');
	require_once ('./../util/hashing.php');
	$utility = new Utility();
	$fields = array('email', 'password');

	$error = false; 
	foreach($fields AS $fieldname) { 
	  if(!isset($_POST[$fieldname]) || empty($_POST[$fieldname])) {
	    echo 'Field '.$fieldname.' missing!<br />';
	    $error = true;
	  }
	}

	if(!$error) {
		$email = $utility->clean_input($_POST['email']);
		$password = $utility->clean_input($_POST['password']);
		$auth = new Auth();
		$main = $auth->activelogin($email, $password, 'customers');
		echo $main;
	}else{
		echo "input error";
	}

?>