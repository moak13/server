<?php 
	require('./../util/functions.php');
	$Utility = new Utility();
	$pducts = new Products();
	$cus_code = $Utility->clean_input($_POST['code']);
	$cuscode = strtolower($cus_code);
	$rcuramount = $Utility->clean_input($_POST['curamount']);
	$checkExist = $Utility->isin($cus_code, "SELECT * FROM promocodes WHERE promo_code = :param");
	if($checkExist != NULL){
		$verify = $Utility->getOneRecord("SELECT * from promocodes where promo_code = '$cuscode'");
		$dbcode = $verify['promo_code'];
		$endate = $verify['date_ending'];
		// $nowdate = new DateTime();
		// $curdate=strtotime($nowdate);
		// $mydate=strtotime($endate);
		if(new DateTime() > new DateTime($endate)){
			echo "this Promo Code is already expired";
		}else{
			$percentage =  $verify['percentage'];
			$newprice = ($percentage / 100) * $rcuramount;
			$newerprice = ($percentage * $rcuramount) / 100;
			echo $newprice;
		}
	}else{
		echo "Promo code does not exist";
	}
?>