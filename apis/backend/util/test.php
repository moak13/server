<?php 
	session_start();
	require('functions.php');
	$Utility = new Utility();
	$Mailing = new Mailing();
	$param = "emmanuel.adeojo@yahoo.comm";
	$sql = "SELECT * FROM customers WHERE email = :email";
	$action = $Utility->exists_by_id($param);
	echo $action;

