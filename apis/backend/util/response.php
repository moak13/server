<?php
require_once('functions.php');


if (isset($_POST['sreg'])) {
	$auth = new Auth();
	$username = $auth->clean_input($_POST['username']);

	if($auth->validate_email($auth->clean_input($_POST['username']))){
		$doregister = $auth->register(array(':email' => $username));
	}else {
		$_SESSION['message'] = "OGA it is only @lmu.edu.ng";
		$_SESSION['messagetype'] ="alert alert-danger";
		$auth->redirect('./../../index.php');
	}
}

if (isset($_POST['login'])) {
	if(isset($_POST['password'])) {
		$auth = new Auth();
		$login = $auth->clean_input($_POST['login']);
		$password = $auth->clean_input($_POST['password']);

		if($auth->validate_email($auth->clean_input($_POST['login']))){
			//check if password correct
			$login = $auth->login($login, $password);
			echo $login;
		}else {
			echo "email not allowed";
		}
	}
	
}


//to dynamically get contestant for a category
if(isset($_POST['getForCat'])) {
	$vote = new Vote();
	$check = $vote->get_all_per_cat(1);
	if($check != NULL) {
		print_r($check);
	}else {
		echo "ghen gehn stuff";
	}
}

///response to vote

if(isset($_POST['vote'])) {
	$cat = $_POST['cat'];
	$currentUser = $_POST['user_id'];
	$contestant = $_POST['contestant'];
}



?>