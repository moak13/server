<?php
class Utility {

	protected $DBcon;

	public function __construct(){
		require_once (dirname(__DIR__).'/util/dbcon.php');
		require_once (dirname(__DIR__).'/vendor/phpmailer/phpmailer/PHPMailerAutoload.php');
 		$db = new dbconnect();
    	$this->DBcon = $db->connect();
	}

	public function run_query($q){
		if($this->DBcon->query($q)){
			return true;
		} else {
			return false;
		};
	}

	public function activate_acc($obj){
		$ref = $obj['ref'];
		$mbox = $obj['email'];

		$detailsIsCorrect = $this->getOne("SELECT * FROM customers WHERE activationCode = '$ref' and email = '$mbox'");

		if($detailsIsCorrect){
			if($detailsIsCorrect['activationStatus'] == 0){
				$activate = "UPDATE customers SET activationStatus = 1 WHERE activationCode = '$ref' and email = '$mbox'";
				if($this->run_query($activate)){
					return "done";
				} else {
					return "couldn't verify";
				}

			} elseif($detailsIsCorrect['activationStatus'] == 1){
				return "already verified";
			} else {
				return "error";
			}
			
		} else {
			return "error";
		}
	}

	public function update($table,$values=array(),$where){
        $args=array();
		foreach($values as $field=>$value){
			$args[]=$field.'="'.$value.'"';
		}
		$spilt = implode(',',$args);
		$sql='UPDATE '.$table.' SET '.$spilt.' WHERE '.$where;
   		if($q=$this->DBcon->prepare($sql)){
   			if ($q->execute()) {
   				return true;
   			}
   		}
   		return false;
    }

	public function updatelong($table, $columns, $data, $where) {

	    foreach ( $columns as $column ) {
	        $update .= " " . $column . " = :" . $column . ", ";
	    }
	    $update = trim($update, ", ");
	    $where_prepare = " " . $where[0] . " = :" . $where[0];
	    $prepare = "UPDATE {$table} SET {$update} WHERE {$where_prepare}";

	    $counter = 0;
	    $execute_data = array();
	    foreach ( $columns as $column ) {
	        $execute_data[":{$column}"] = $data[$counter];
	        $counter++;
	    }
	    $where_0 = $where[0];
	    $execute_data[":{$where_0}"] = $where[1];

	    $stmt = $this->DBcon->prepare($prepare);
	    $stmt->execute($execute_data);
	    $affected_rows = $stmt->rowCount();

	    if ( $row_count < 1 ) {
	        return false;
	    } else {
	        return true;
	    }
	}

	public static function clean_input($in) {
		$res = stripslashes($in);
		$res = trim($res);
		$res = filter_var($res, FILTER_SANITIZE_STRING);
		return $res;
	}


	public function easyquery($query) {
		$r = $this->DBcon->prepare($query);
		$r->execute();
		$message = $r->fetchAll();
		return $message;
	}

	public function reset($mail, $obj){
		if(!empty($mail) && !empty($obj)){
			$hash = $obj['hash'];
			$umail = $obj['email'];
			$newpass = $obj['newpass'];
			require_once ('hashing.php');
			$newpasshash = passwordHash::hash($newpass);


			$q1 = $this->getOne("SELECT * FROM passrecovery where email = '$umail' and recovery_code = '$hash' and status = 0 ORDER BY id DESC");
			$q2 = $this->DBcon->query("UPDATE passrecovery SET status = 1 WHERE email = '$umail'");
			$q3 = $this->DBcon->query("UPDATE customers SET password = '$newpasshash', cleartext = '$newpass' WHERE email = '$umail'");

			if($q1){ //if the person has a record in the passreset tbl then update the pass reset table
				if($q2){ //if updated pass table  then update gen usr tbl
					if($q3){ //if updated gen usr tablle return true and end;
						return true;
						// return "true with q3";
					} else {
						return false;
						// return "error with q3";
					}
				} else {
					return false;
					// return "error with q2";
				}
			} else {
				return false;
				// return "error with q3";
			}

		} else {
			return false;
		}
	}

	public function getOne($query){
		$r = $this->DBcon->query($query. ' LIMIT 1') or die($this->db->error . __LINE__);
		$r->execute();
		$message = $r->fetch();
		return $message;
	}

	public function getOneRecord($query) {
		$r = $this->DBcon->prepare($query.' LIMIT 1') or die($this->conn->error.__LINE__);
		$r->execute();
		$message = $r->fetch();
		return $message;
	}

	public function cartfetch($query){
		$r = $this->DBcon->prepare($query.' LIMIT 1') or die($this->conn->error.__LINE__);
		$r->execute();
		return $r;
	}


	public function getatran($table){
		$r = $this->DBcon->prepare("SELECT * FROM $table order by RAND() LIMIT 1");
		$r->execute();
		$message = $r->fetch();
		return $message;
	}

	public function searchproduct($string){
		$keywords = explode(' ', $string);
		foreach($keywords as $words) {
				$searchTermKeywords[] = "title LIKE '%$words%'";
				$searchTermKeywords2[] = "description LIKE '%$words%'";
		}
		// $sql = "select title from products where title like '%$string%'";
		$sql = "Select * from products where ".implode(' AND ', $searchTermKeywords)." or ".implode(' AND ', $searchTermKeywords2)." limit 10";
		$r = $this->DBcon->prepare($sql);
		$r->execute();
		$message = $r->fetchall();
		return $message;
	}

	public function validate_email($email) {
		require_once 'mailValidator.php';
		$validator = new Validator();
		return $validator->validate_by_domain($email);
	}

	public function exists_by_id ($param) {
	    // check if object exists by id
	    $stm = $this->DBcon->prepare('select count(*) from `customers` where `email`=:column');
	    $stm->bindParam(':column', $param);
	    $stm->execute();
	    $res = $stm->fetchColumn();

	    if ($res > 0) {
	        return true;
	    }
	    else {
	        return NULL;
	    }
	}

	public function isExist($email, $table) {
		try {
			$stmt = $this->DBcon->prepare("SELECT * FROM $table WHERE email = :email");
			$stmt->execute(array(':email' => $email));
			$res = $stmt->fetch(PDO::FETCH_ASSOC);
			if($stmt->rowCount() > 0){
			    return $res;
			}else{
			    return NULL;
			}
		} catch(PDOException $ex) {
			return NULL;
		}
	}

	public function isin($param, $query) {
		// SELECT * FROM $table WHERE email = :email
		try {
			$stmt = $this->DBcon->prepare($query);
			$stmt->execute(array(':param' => $param));
			$res = $stmt->fetch(PDO::FETCH_ASSOC);
			if($res->rowCount() > 0){
			    return $res;
			}else{
			    return NULL;
			}
		} catch(PDOException $ex) {
			return NULL;
		}
	}

	public function insert($table, array $fields, array $values) {
	    $numFields = count($fields);
	    $numValues = count($values);
	    if($numFields === 0 or $numValues === 0)
	        throw new Exception("At least one field and value is required.");
	    if($numFields !== $numValues)
	        throw new Exception("Mismatched number of field and value arguments.");

	    $fields = '`' . implode('`,`', $fields) . '`';
	    $values = "'" . implode("','", $values) . "'";
	    $sql = "INSERT INTO {$table} ($fields) VALUES($values)";
		
		if ($q=$this->DBcon->prepare ( $sql )) {
	        if ($q->execute()) {
	            return true;
	        }else{
	        	return false;
	        }
	    }
	}

	public function isAuth () {
		if(isset($_SESSION['user_id'])) {
			return $_SESSION['user_id'];
		}else {
			return NULL;
		}
	}

	public static function redirect ($url) {
		header("Location: $url");
	}
}

class paginate extends Utility{
	function __construct() {
		parent::__construct();
		require_once 'hashing.php';
	}

	public function dataview($query){
		$stmt = $this->DBcon->prepare($query);
		$stmt->execute();

		if($stmt->rowCount()>0){
					 while($row=$stmt->fetch(PDO::FETCH_ASSOC)){
							?>
							<!-- <tr>
							<td><?php //echo $row['tuts_id']; ?></td>
							<td><?php //echo $row['tuts_title']; ?></td>
							<td><a href="<?php //echo $row['tuts_link']; ?>">Open</a></td>
							</tr> -->
							<?php
					 }
		}else{
					 ?>
					 <tr>
					 <td>No data in your DATABASE...</td>
					 </tr>
					 <?php
		}
	}

	public function paging($query,$data_per_Page){
			$starting_position=0;
			if(isset($_GET["page_no"])){
						$starting_position=($_GET["page_no"]-1)*$data_per_Page;
			}
			$query2=$query." limit $starting_position,$data_per_Page";
			return $query2;
	}

	public function paginglink($query,$data_per_Page){
		 $self = $_SERVER['PHP_SELF'];
		 $stmt = $this->DBcon->prepare($query);
		 $stmt->execute();
		 $total_no_of_records = $stmt->rowCount();
		 if($total_no_of_records > 0){
				 ?><tr><td colspan="3">
				 <?php
				 $whole_count_Of_Pages=ceil($total_no_of_records/$data_per_Page);
				 $current_page=1;
				 if(isset($_GET["page_no"])){
						$current_page=$_GET["page_no"];
				 }
				 if($current_page!=1){
						$previous =$current_page-1;
						echo "<a href='".$self."?page_no=1'>First</a>&nbsp;&nbsp;";
						echo "<a href='".$self."?page_no=".$previous."'>Previous</a>&nbsp;&nbsp;";
				 }
				 for($i=1;$i<=$whole_count_Of_Pages;$i++){
					if($i==$current_page){
							echo "<strong><a href='".$self."?page_no=".$i."' class='active'>".$i."</a></strong>&nbsp;&nbsp;";
					}else{
						 echo "<a href='".$self."?page_no=".$i."'>".$i."</a>&nbsp;&nbsp;";
				  }
				}
				if($current_page!=$whole_count_Of_Pages){
						$next=$current_page+1;
						echo "<a href='".$self."?page_no=".$next."'>Next</a>&nbsp;&nbsp;";
						echo "<a href='".$self."?page_no=".$whole_count_Of_Pages."'>Last</a>&nbsp;&nbsp;";
				}
					?></td></tr><?php
			}
	}
}


class Auth extends Utility{

	function __construct() {
		parent::__construct();
		require_once 'hashing.php';
	}

	public function justkill() {
		// print_r($_SESSION);
		unset($_SESSION['user_id']);
		unset($_SESSION['email']);
		unset($_SESSION['timestamp']);
		session_destroy();
		session_unset();
	}

	public function logout() {
		// print_r($_SESSION);
		unset($_SESSION['user_id']);
		unset($_SESSION['email']);
		unset($_SESSION['timestamp']);
		session_destroy();
		session_unset();
		$this->redirect('../../login.php');
	}

	public function activelogout() {
		// print_r($_SESSION);
		unset($_SESSION['user_id']);
		unset($_SESSION['email']);
		unset($_SESSION['timestamp']);
		session_destroy();
		session_unset();
	}

	public function random_char(){
		// where char stands for the string u want to randomize
		$char = 'abcdefghijklmnopqrstuvwxyz1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$char_length = 20;
		$cl = strlen($char);
		$randomize = '';
		for($i = 0; $i < $char_length; $i++ ){
			$randomize .= $char[rand(0, $cl - 1)]; 
		}
		return $randomize;
	}

	public function getSessions () {
		return $_SESSION;
	}

	public function unsetSessions ($sessionName) {
		unset($_SESSION['sessionName']);
	}

	public function forgotepass($gparam, $table){
		require_once 'hashing.php';
		$token = $this->random_char();
		$param = $this->clean_input($gparam);
		$sql = "SELECT * FROM customers WHERE email = :email";
		if($this->isin($param, $sql) !== NULL) {
			try {
				$send_verify = new Mailing();
				if($send_verify->passrecover($param, $token)){
					$fields = array('userid', 'recovery_code');
					$values = array($_SESSION['user_id'], $token);
					if($this->insert($table, $fields,  $values)){
						$_SESSION['message'] = "recover link has been sent to your mail";
						$_SESSION['messagetype'] ="alert alert-danger";
						$this->redirect('./../../password/reset.php');
					}else{
						$_SESSION['message'] = "Error inserting";
						$_SESSION['messagetype'] ="alert alert-danger";
						$this->redirect('./../../password/reset.php');
					}
				}else{
					$_SESSION['message'] = "mailing error";
					$_SESSION['messagetype'] ="alert alert-danger";
					$this->redirect('./../../register.php');
				}
			} catch(PDOException $ex) {
					$_SESSION['message'] = "Registration Failed";
					$_SESSION['messagetype'] ="alert alert-danger";
					$this->redirect('./../../password/reset.php');
			}
		}else{
			$_SESSION['message'] = "user not registered";
			$_SESSION['messagetype'] ="alert alert-danger";
			$this->redirect('./../../password/reset.php');
		}
	}
		    
	//register function
	public function register($table, array $fields, array $values) {
		//session_start();
		require_once 'hashing.php';
		$token = $this->random_char();
		if($this->isExist($values['email'], 'customers') == NULL) {
			try {
				$send_verify = new Mailing();
				if($send_verify->mail_verification($values['email'], $token)){
					if($this->insert($table, $fields,  $values)){
							$_SESSION['message'] = "Registered Successfully Please Check your Mail for Activation";
							$_SESSION['messagetype'] ="alert alert-success";
							$this->redirect('./../../register.php');
					}else{
						$_SESSION['message'] = "Error inserting";
						// $_SESSION['message'] = $send_verify->mail_verification($values['email'], $token);
						$_SESSION['messagetype'] ="alert alert-danger";
						$this->redirect('./../../register.php');
					}
				}else{
					$_SESSION['message'] = "mailing error";
					$_SESSION['messagetype'] ="alert alert-danger";
					$this->redirect('./../../register.php');
				}
			} catch(PDOException $ex) {
					$_SESSION['message'] = "Registration Failed";
					$_SESSION['messagetype'] ="alert alert-danger";
					$this->redirect('./../../register.php');
			}
		}else{
			$_SESSION['message'] = "User Already Registered";
			$_SESSION['messagetype'] ="alert alert-danger";
			$this->redirect('./../../register.php');
		}	
	}

	// login function
	public function login($email, $password, $table) {
		try {
			$stmt = $this->DBcon->prepare("SELECT _id, email,password FROM $table WHERE (email=:email)"); 
		    $stmt->bindParam(':email', $email);
			$stmt->execute();
			$res = $stmt->fetch(PDO::FETCH_ASSOC);
	    	if ( $stmt->rowCount() > 0 ) {
	    		if(passwordHash::check_password($res['password'], $password)){
	    			if($res['activationStatus'] != "0"){
		    			session_start();
	    				$_SESSION['user_id'] = $res['_id'];
			    		$_SESSION['email'] = $res['email'];
			    		$_SESSION['timestamp']=time();
			    		$this->redirect('./../../dashboard.php');
			    	}else{
			    		//not activated
		    			$_SESSION['message'] = "account Not ACTIVATED";
						$_SESSION['messagetype'] ="alert alert-danger";
						$this->redirect('./../../login.php');
				    	}
	    		}else{
	    			//wrong password
	    			$_SESSION['message'] = "wrong password";
					$_SESSION['messagetype'] ="alert alert-danger";
					$this->redirect('./../../login.php');
		    	} 
		    }else {
	    		//user does not exist
    			$_SESSION['message'] = "User does not exist";
				$_SESSION['messagetype'] ="alert alert-danger";
				$this->redirect('./../../login.php');
	    		exit;
	    	}
		}catch (PDOException $ex) {
			echo "PDO did not work";
		}
	}

	public function activelogin($email, $password, $table) {
		try {
			$stmt = $this->DBcon->prepare("SELECT _id, email,password FROM $table WHERE (email=:email)"); 
		    $stmt->bindParam(':email', $email);
			$stmt->execute();
			$res = $stmt->fetch(PDO::FETCH_ASSOC);
	    	if ( $stmt->rowCount() > 0 ) {
	    		if(passwordHash::check_password($res['password'], $password)){
	    			if (session_status() == PHP_SESSION_NONE) {
					    session_start();
					}
    				$_SESSION['user_id'] = $res['_id'];
		    		$_SESSION['email'] = $res['email'];
		    		$_SESSION['timestamp']=time();
		    		// $res = json_encode("success");
		    		return "success";
	    		}else{
	    			return "wrong_password";
		    	} 
		    }else {
	    		//user does not exist
    			return "User_not_exist";
	    	}
		}catch (PDOException $ex) {
			return "PDO did not work";
		}
	}
	

	//function to activate an account
	public function verify ($id, $token){
		try {
			$stmt = $this->DBcon->prepare("SELECT * FROM user WHERE (_id = :id and token = :token)");
			$stmt->execute(array(':id' => $id, 'token' => $token));
			$res = fetch(PDO::FETCH_ASSOC);
			if($stmt->rowCount > 0) {
				try{
					$active_stmt = $this->DBcon->prepare("UPDATE user SET active = 1 WHERE (_id = :id and token = :token)");
					$active_stmt->execute(array(':id' => $id, 'token' => $token));

					//account activation successful
				} catch(PDOException $e){
					//account activation failed
				}
			}
		} catch(PDOException $ex) {
			//error
		}
	}
}


class Products extends Utility{
	private $category_id;
	private $contestant;

	function __construct__() {
		parent::__construct();
	}

	public function get_cat_name($cat_id) {
		try {
			$stmt = $this->DBcon->prepare("SELECT * FROM products WHERE cat_id = :category_id");
			$stmt->execute(array(':category_id' => $cat_id));
			$res = $stmt->fetch(PDO::FETCH_ASSOC);
			if($stmt->rowCount() > 0){
			    return $res;
			}else{
			    return NULL;
			}
		} catch(PDOException $ex) {
			return NULL;
		}
	}

	public function pushconnection(){
		$DBconection = $this->DBcon;
		return $DBconection;
	}

	public function getAll($table){
		$SQL = "SELECT * from $table ORDER BY RAND() LIMIT 10";
		$q = $this->DBcon->query($SQL) or die("Failed");
		while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
			$data[] = $r;
		}
		return $data;
	}

	public function getit($SQL){
		$q = $this->DBcon->query($SQL) or die("Failed");
		while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
			$data[] = $r;
		}
		if(!empty($data)){
			return $data;
		}else{
			return '';
		}
	}

	

	public function getcount($constId, $catId){
		try {
			$stmt = $this->DBcon->prepare("SELECT * FROM vote WHERE contestant_id = :contestant_id AND category_id = :category_id");
			$stmt->execute(array(':contestant_id' => $constId, ':category_id' => $catId));
			$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return $stmt->rowCount();
		} catch(PDOException $ex) {
			return NULL;
		}
	}


	public function get_all_per_cat ($cat_id){
		try {
			$stmt = $this->DBcon->prepare("SELECT * FROM contestant WHERE category_id =:category_id");
			$stmt->execute(array(':category_id' => $cat_id));
			$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if($stmt->rowCount() > 0 ) {
				return $res;
			}else{
				return NULL;
			}
		}
		catch (PDOException $e) {
			echo "Something Went Wrong, Try Again";
		}
	}
}

class Mailing extends Utility{

	private $mail;

	public function __construct(){
		require_once(dirname(__DIR__).'/vendor/phpmailer/phpmailer/PHPMailerAutoload.php');
		require_once(dirname(__DIR__).'/config.php');
	}

	public function mail_verification($email, $token) {
			require_once(dirname(__DIR__).'/vendor/phpmailer/phpmailer/PHPMailerAutoload.php');
		// TCP port to DBconect to
			$this->mail = new PHPMailer;
			$this->mail->isSMTP(); 
			// $this->mail->isMail();  
			// $this->mail->SMTPDebug = 3;                               // Enable verbose debug output
			                                     // Set mailer to use SMTP
			//add these codes if not written
			$this->mail->Host = 'smtp.gmail.com';  // Specify main and backup SMTP servers
			$this->mail->SMTPAuth = true;                               // Enable SMTP authentication
			// $this->mail->Username = SMTP_USER;                 // SMTP username
			$this->mail->Username = 'adeojo.emmanuel@lmu.edu.ng';                 // SMTP username
			// $this->mail->Password = SMTP_PASSWORD;                           // SMTP password
			$this->mail->Password = './configure.';                           // SMTP password
			$this->mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
			$this->mail->Port = 587;
			$this->mail->setFrom('3plecoin.com', '3plecoin  - Verification Email');
			
			$this->mail->addAddress($email, ''); 
       // Add attachments
			    // Optional name
			$this->mail->isHTML(true);                                  // Set email format to HTML

			$this->mail->Subject = '3plecoin Verification Email';
			$this->mail->From = "adeojo.emmanuel@lmu.edu.ng";
			$this->mail->FromName = "3plecoin";
			$this->mail->Subject = "3plecoin  - Verification Email";
			$msg = '<html>
					  <head>
					    <title>Activation Email</title>
					  </head>
					  <body>  
					    <div id="" style="display: block;" class="rl-cache-class b-text-part html">
					      <div data-x-div-type="html">
					        <div data-x-div-type="body" style="-webkit-text-size-adjust: none;-ms-text-size-adjust: 100%;background-color: #F3F3F3">
					          <table cellspacing="0" cellpadding="0" border="0" width="90%">
					            <tbody>
					              <tr> 
					                <td style="background-color: #F3F3F3" align="center" valign="top"> 
					                  <table cellspacing="0" cellpadding="0" border="0" width="580"> 
					                    <tbody>
					                      <tr>
					                        
					                      </tr> 
					                      <tr> 
					                        <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 15px;line-height: 19px;font-weight: bold;background-color: #FFFFFF" align="center" width="580"> 
					                          <div>
					                            <div>
					                              <a style="outline: none" target="_blank" tabindex="-1"></a>
					                            </div>
					                          </div> 
					                        </td> 
					                      </tr> 
					                      <tr> 
					                        <td style="padding-top: 30px;padding-right: 40px;padding-bottom: 30px;padding-left: 40px;background-color: #FFFFFF" align="center" width="580"> 
					                          <table cellspacing="0" margin cellpadding="0" border="0" width="580"> 
					                            <tbody>
					                              <tr>
					                              </tr> 
					                              <tr> 
					                                <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 15px;line-height: 19px;font-weight: bold;background-color: #FFFFFF" align="center" width="580"> 
					                                  <div>
					                                    <div>
					                                      <a style="outline: none" target="_blank" tabindex="-1">
					                                      </a>
					                                    </div>
					                                  </div> 
					                                </td> 
					                              </tr> 
					                              <tr> 
					                                <td style="padding-top: 30px;padding-right: 40px;padding-bottom: 30px;padding-left: 40px;background-color: #FFFFFF" align="center" width="580"> 
					                                  <table cellspacing="0" cellpadding="0" border="0"> 
					                                    <tbody>
					                                      <tr> 
					                                        <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 23px;line-height: 28px"> 
					                                          <div style="font-size: 23px;color: #313131;font-weight: bold;text-decoration: none">
					                                            <div style="text-align: center">
					                                              <strong>Welcome to 3plecoin Account Activation</strong>
					                                            </div>
					                                          </div> 
					                                        </td> 
					                                      </tr> 
					                                      <tr> 
					                                        <td style="font-size: 10px;line-height: 10px">
					                                          <img alt="" style="display: block" data-x-broken-src="../images/gif.gif" height="10" width="1">
					                                        </td> 
					                                      </tr> 
					                                      <tr> 
					                                        <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 15px;line-height: 19px;color: #2e2e31" align="left"> 
					                                          <div> 
					                                            <div> 
					                                            </div> 
					                                            <div style="text-align: center">Click the link Below to activate your account.
					                                               
					                                              <center>This is an automated message. Do not reply.</center><br> 
					                                              
					                                            </div> 
					                                          </div> 
					                                        </td> 
					                                      </tr> 
					                                      <tr> 
					                                        <td height="30">
					                                          <img alt="" style="display: block" data-x-broken-src="../images/gif.gif" height="30" width="1">
					                                        </td> 
					                                      </tr> 
					                                      <tr> 
					                                        <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 15px;font-weight: bold" align="center"> 
					                                          <div> 
					                                            <div>
					                                              <a href="https://localhost/3plecoin/activation/index.php?email='. $email .'&action=activate&token='.$token . '" style="background-color: #800000;border-radius: 2px;color: #ffffff;display: inline-block;line-height: 40px;text-align: center;text-decoration: none;width: 160px;-webkit-text-size-adjust: none" target="_blank" rel="external nofollow noopener noreferrer" tabindex="-1">Click To Activate
					                                              </a>
					                                            </div> 
					                                            <div> 
					                                            </div> 
					                                          </div> 
					                                        </td> 
					                                      </tr> 
					                                    </tbody>
					                                  </table> 
					                                </td> 
					                              </tr> 
					                              <tr> 
					                                <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 15px;line-height: 19px;font-weight: bold;background-color: #FFFFFF" align="center" width="580"> 
					                                  <div>
					                                    
					                                  </div> 
					                                </td> 
					                              </tr> 
					                              <tr> 
					                                <td style="background-color: #4C4C4C"> 
					                                  <table style="background-color: #4C4C4C" cellspacing="0" cellpadding="0" border="0" align="center">
					                                    <tbody>
					                                      <tr> 
					                                        <td width="15">
					                                          <img alt="" style="display: block" data-x-broken-src="../images/gif.gif" height="1" width="15"></td> 
					                                        <td width="279">
					                                        <img alt="" style="display: block" data-x-broken-src="../images/gif.gif" height="1" width="279"></td> 
					                                      </tr>
					                                    </tbody>
					                                  </table> 
					                                </td> 
					                              </tr> 
					                              <tr> 
					                                <td style="padding-top: 30px;padding-right: 30px;padding-bottom: 30px;padding-left: 30px"> 
					                                  <table cellspacing="0" cellpadding="0" border="0" align="center"> 
					                                    <tbody>
					                                      <tr> 
					                                        <td style="font-family: "Helvetica Neue", Arial, Helvetica, sans-serif;color: #808285;font-size: 13px;line-height: 19px;font-weight: bold" align="center" valign="top">
					                                          <a href="http://w3coin.com" target="_blank" style="color: #808285;text-decoration: none" rel="external nofollow noopener noreferrer" tabindex="-1">3PLECOIN</a>
					                                        </td> 
					                                      </tr> 
					                                    </tbody>
					                                  </table> 
					                                </td> 
					                              </tr> 
					                            </tbody>
					                          </table>
					                        </td>
					                      </tr>
					                    </tbody>
					                  </table>
					                </td>
					              </tr>
					            </tbody>
					          </table>
					        </div>
					      </div>
					    </div>';
			
			// echo $this->mail->Body;
			$this->mail->Body = $msg;
			$this->mail->IsHTML(true);

			if(!$this->mail->send()) {
			    return false;
			    // return 'Mailer error: ' . $this->mail->ErrorInfo;
			} else {
			    return true;
			}

	}

	public function passrecover($email, $token) {
			require_once(dirname(__DIR__).'/vendor/phpmailer/phpmailer/PHPMailerAutoload.php');
		// TCP port to DBconect to
			$this->mail = new PHPMailer;
			$this->mail->isSMTP(); 
			// $this->mail->isMail();  
			// $this->mail->SMTPDebug = 3;                               // Enable verbose debug output
			                                     // Set mailer to use SMTP
			//add these codes if not written
			$this->mail->Host = 'smtp.gmail.com';  // Specify main and backup SMTP servers
			$this->mail->SMTPAuth = true;                               // Enable SMTP authentication
			// $this->mail->Username = SMTP_USER;                 // SMTP username
			$this->mail->Username = 'adeojo.emmanuel@lmu.edu.ng';                 // SMTP username
			// $this->mail->Password = SMTP_PASSWORD;                           // SMTP password
			$this->mail->Password = './configure.';                           // SMTP password
			$this->mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
			$this->mail->Port = 587;
			$this->mail->setFrom('3plecoin.com', '3plecoin  - Verification Email');
			
			$this->mail->addAddress($email, ''); 
       // Add attachments
			    // Optional name
			$this->mail->isHTML(true);                                  // Set email format to HTML

			$this->mail->Subject = '3plecoin Verification Email';
			$this->mail->From = "adeojo.emmanuel@lmu.edu.ng";
			$this->mail->FromName = "3plecoin";
			$this->mail->Subject = "3plecoin  - Verification Email";
			$msg = '<html>
					  <head>
					    <title>Activation Email</title>
					  </head>
					  <body>  
					    <div id="" style="display: block;" class="rl-cache-class b-text-part html">
					      <div data-x-div-type="html">
					        <div data-x-div-type="body" style="-webkit-text-size-adjust: none;-ms-text-size-adjust: 100%;background-color: #F3F3F3">
					          <table cellspacing="0" cellpadding="0" border="0" width="90%">
					            <tbody>
					              <tr> 
					                <td style="background-color: #F3F3F3" align="center" valign="top"> 
					                  <table cellspacing="0" cellpadding="0" border="0" width="580"> 
					                    <tbody>
					                      <tr>
					                        
					                      </tr> 
					                      <tr> 
					                        <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 15px;line-height: 19px;font-weight: bold;background-color: #FFFFFF" align="center" width="580"> 
					                          <div>
					                            <div>
					                              <a style="outline: none" target="_blank" tabindex="-1"></a>
					                            </div>
					                          </div> 
					                        </td> 
					                      </tr> 
					                      <tr> 
					                        <td style="padding-top: 30px;padding-right: 40px;padding-bottom: 30px;padding-left: 40px;background-color: #FFFFFF" align="center" width="580"> 
					                          <table cellspacing="0" margin cellpadding="0" border="0" width="580"> 
					                            <tbody>
					                              <tr>
					                              </tr> 
					                              <tr> 
					                                <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 15px;line-height: 19px;font-weight: bold;background-color: #FFFFFF" align="center" width="580"> 
					                                  <div>
					                                    <div>
					                                      <a style="outline: none" target="_blank" tabindex="-1">
					                                      </a>
					                                    </div>
					                                  </div> 
					                                </td> 
					                              </tr> 
					                              <tr> 
					                                <td style="padding-top: 30px;padding-right: 40px;padding-bottom: 30px;padding-left: 40px;background-color: #FFFFFF" align="center" width="580"> 
					                                  <table cellspacing="0" cellpadding="0" border="0"> 
					                                    <tbody>
					                                      <tr> 
					                                        <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 23px;line-height: 28px"> 
					                                          <div style="font-size: 23px;color: #313131;font-weight: bold;text-decoration: none">
					                                            <div style="text-align: center">
					                                              <strong>Welcome to 3plecoin Account Activation</strong>
					                                            </div>
					                                          </div> 
					                                        </td> 
					                                      </tr> 
					                                      <tr> 
					                                        <td style="font-size: 10px;line-height: 10px">
					                                          <img alt="" style="display: block" data-x-broken-src="../images/gif.gif" height="10" width="1">
					                                        </td> 
					                                      </tr> 
					                                      <tr> 
					                                        <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 15px;line-height: 19px;color: #2e2e31" align="left"> 
					                                          <div> 
					                                            <div> 
					                                            </div> 
					                                            <div style="text-align: center">Click the link Below to activate your account.
					                                               
					                                              <center>This is an automated message. Do not reply.</center><br> 
					                                              
					                                            </div> 
					                                          </div> 
					                                        </td> 
					                                      </tr> 
					                                      <tr> 
					                                        <td height="30">
					                                          <img alt="" style="display: block" data-x-broken-src="../images/gif.gif" height="30" width="1">
					                                        </td> 
					                                      </tr> 
					                                      <tr> 
					                                        <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 15px;font-weight: bold" align="center"> 
					                                          <div> 
					                                            <div>
					                                              <a href="https://localhost/3plecoin/activation/index.php?email='. $email .'&action=reset&token='.$token.'"style="background-color: #800000;border-radius: 2px;color: #ffffff;display: inline-block;line-height: 40px;text-align: center;text-decoration: none;width: 160px;-webkit-text-size-adjust: none" target="_blank" rel="external nofollow noopener noreferrer" tabindex="-1">Click To Activate
					                                              </a>
					                                            </div> 
					                                            <div> 
					                                            </div> 
					                                          </div> 
					                                        </td> 
					                                      </tr> 
					                                    </tbody>
					                                  </table> 
					                                </td> 
					                              </tr> 
					                              <tr> 
					                                <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 15px;line-height: 19px;font-weight: bold;background-color: #FFFFFF" align="center" width="580"> 
					                                  <div>
					                                    
					                                  </div> 
					                                </td> 
					                              </tr> 
					                              <tr> 
					                                <td style="background-color: #4C4C4C"> 
					                                  <table style="background-color: #4C4C4C" cellspacing="0" cellpadding="0" border="0" align="center">
					                                    <tbody>
					                                      <tr> 
					                                        <td width="15">
					                                          <img alt="" style="display: block" data-x-broken-src="../images/gif.gif" height="1" width="15"></td> 
					                                        <td width="279">
					                                        <img alt="" style="display: block" data-x-broken-src="../images/gif.gif" height="1" width="279"></td> 
					                                      </tr>
					                                    </tbody>
					                                  </table> 
					                                </td> 
					                              </tr> 
					                              <tr> 
					                                <td style="padding-top: 30px;padding-right: 30px;padding-bottom: 30px;padding-left: 30px"> 
					                                  <table cellspacing="0" cellpadding="0" border="0" align="center"> 
					                                    <tbody>
					                                      <tr> 
					                                        <td style="font-family: "Helvetica Neue", Arial, Helvetica, sans-serif;color: #808285;font-size: 13px;line-height: 19px;font-weight: bold" align="center" valign="top">
					                                          <a href="http://w3coin.com" target="_blank" style="color: #808285;text-decoration: none" rel="external nofollow noopener noreferrer" tabindex="-1">3PLECOIN</a>
					                                        </td> 
					                                      </tr> 
					                                    </tbody>
					                                  </table> 
					                                </td> 
					                              </tr> 
					                            </tbody>
					                          </table>
					                        </td>
					                      </tr>
					                    </tbody>
					                  </table>
					                </td>
					              </tr>
					            </tbody>
					          </table>
					        </div>
					      </div>
					    </div>';
			
			// echo $this->mail->Body;
			$this->mail->Body = $msg;
			$this->mail->IsHTML(true);

			if(!$this->mail->send()) {
			    return false;
			    // return 'Mailer error: ' . $this->mail->ErrorInfo;
			} else {
			    return true;
			}

	}

	public function hotline($email, $message, $name) {
		require_once(dirname(__DIR__).'/vendor/phpmailer/phpmailer/PHPMailerAutoload.php');
		// TCP port to DBconect to
		$this->mail = new PHPMailer;
			//$mail->SMTPDebug = 3;                               // Enable verbose debug output
			$this->mail->isSMTP();                                      // Set mailer to use SMTP
			//add these codes if not written
			$this->mail->IsSMTP();
			$this->mail->SMTPAuth   = true;  
			$this->mail->Host = 'smtp.gmail.com';  // Specify main and backup SMTP servers
			$this->mail->SMTPAuth = true;                               // Enable SMTP authentication
			$this->mail->Username = SMTP_USER;                 // SMTP username
			$this->mail->Password = SMTP_PASSWORD;                           // SMTP password
			$this->mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
			$this->mail->Port = 25;
			$this->mail->setFrom('debexstore.com', 'Debex store  - Verification Email');
			
			$this->mail->addAddress($email, ''); 
       // Add attachments
			    // Optional name
			$this->mail->isHTML(true);                                  // Set email format to HTML

			$this->mail->Subject = 'Debex store News Letter';
			$this->mail->From = "debexstore.com";
			$this->mail->FromName = "debexstore";
			$this->mail->Subject = "Debex store  - News Letter";
			$msg = '<html>
					  <head>
					    <title>News Letter</title>
					  </head>
					  <body>  
					    <div id="" style="display: block;" class="rl-cache-class b-text-part html">
					      <div data-x-div-type="html">
					        <div data-x-div-type="body" style="-webkit-text-size-adjust: none;-ms-text-size-adjust: 100%;background-color: #F3F3F3">
					          <table cellspacing="0" cellpadding="0" border="0" width="100%">
					            <tbody>
					              <tr> 
					                <td style="background-color: #F3F3F3" align="center" valign="top"> 
					                  <table cellspacing="0" cellpadding="0" border="0" width="580"> 
					                    <tbody>
					                      <tr>
					                        
					                      </tr> 
					                      <tr> 
					                        <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 15px;line-height: 19px;font-weight: bold;background-color: #FFFFFF" align="center" width="580"> 
					                          <div>
					                            <div>
					                              <a style="outline: none" target="_blank" tabindex="-1"></a>
					                            </div>
					                          </div> 
					                        </td> 
					                      </tr> 
					                      <tr> 
					                        <td style="padding-top: 30px;padding-right: 40px;padding-bottom: 30px;padding-left: 40px;background-color: #FFFFFF" align="center" width="580"> 
					                          <table cellspacing="0" margin cellpadding="0" border="0" width="580"> 
					                            <tbody>
					                              <tr>
					                              </tr> 
					                              <tr> 
					                                <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 15px;line-height: 19px;font-weight: bold;background-color: #FFFFFF" align="center" width="580"> 
					                                  <div>
					                                    <div>
					                                      <a style="outline: none" target="_blank" tabindex="-1">
					                                      </a>
					                                    </div>
					                                  </div> 
					                                </td> 
					                              </tr> 
					                              <tr> 
					                                <td style="padding-top: 30px;padding-right: 40px;padding-bottom: 30px;padding-left: 40px;background-color: #FFFFFF" align="center" width="580"> 
					                                  <table cellspacing="0" cellpadding="0" border="0"> 
					                                    <tbody>
					                                      <tr> 
					                                        <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 23px;line-height: 28px"> 
					                                          <div style="font-size: 23px;color: #313131;font-weight: bold;text-decoration: none">
					                                            <div style="text-align: center">
					                                              <strong>Welcome to Debex Store</strong>
					                                            </div>
					                                          </div> 
					                                        </td> 
					                                      </tr> 
					                                      <tr> 
					                                        <td style="font-size: 10px;line-height: 10px">
					                                          <img alt="" style="display: block" data-x-broken-src="../images/gif.gif" height="10" width="1">
					                                        </td> 
					                                      </tr> 
					                                      <tr> 
					                                        <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 15px;line-height: 19px;color: #2e2e31" align="left"> 
					                                          <div> 
					                                            <div> 
					                                            </div> 
					                                            <div style="text-align: center">Click the link Below to activate your account.
					                                               
					                                              <center>This is an automated message. Do not reply.</center><br> 
					                                              
					                                            </div> 
					                                          </div> 
					                                        </td> 
					                                      </tr> 
					                                      <tr> 
					                                        <td height="30">
					                                          <img alt="" style="display: block" data-x-broken-src="../images/gif.gif" height="30" width="1">
					                                        </td> 
					                                      </tr> 
					                                      <tr> 
					                                        <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 15px;font-weight: bold" align="center"> 
					                                          <div> 
					                                            <div>
					                                            <p>message test test test</p>  
					                                            
					                                            </div> 
					                                            <div> 
					                                            </div> 
					                                          </div> 
					                                        </td> 
					                                      </tr> 
					                                    </tbody>
					                                  </table> 
					                                </td> 
					                              </tr> 
					                              <tr> 
					                                <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 15px;line-height: 19px;font-weight: bold;background-color: #FFFFFF" align="center" width="580"> 
					                                  <div>
					                                    
					                                  </div> 
					                                </td> 
					                              </tr> 
					                              <tr> 
					                                <td style="background-color: #4C4C4C"> 
					                                  <table style="background-color: #4C4C4C" cellspacing="0" cellpadding="0" border="0" align="center">
					                                    <tbody>
					                                      <tr> 
					                                        <td width="15">
					                                          <img alt="" style="display: block" data-x-broken-src="../images/gif.gif" height="1" width="15"></td> 
					                                        <td width="279">
					                                        <img alt="" style="display: block" data-x-broken-src="../images/gif.gif" height="1" width="279"></td> 
					                                      </tr>
					                                    </tbody>
					                                  </table> 
					                                </td> 
					                              </tr> 
					                              <tr> 
					                                <td style="padding-top: 30px;padding-right: 30px;padding-bottom: 30px;padding-left: 30px"> 
					                                  <table cellspacing="0" cellpadding="0" border="0" align="center"> 
					                                    <tbody>
					                                      <tr> 
					                                        <td style="font-family: "Helvetica Neue", Arial, Helvetica, sans-serif;color: #808285;font-size: 13px;line-height: 19px;font-weight: bold" align="center" valign="top">
					                                          <a href="http://CSEVOTE.LMU.EDU.NG" target="_blank" style="color: #808285;text-decoration: none" rel="external nofollow noopener noreferrer" tabindex="-1">CSE DEV COMMITY</a>
					                                        </td> 
					                                      </tr> 
					                                    </tbody>
					                                  </table> 
					                                </td> 
					                              </tr> 
					                            </tbody>
					                          </table>
					                        </td>
					                      </tr>
					                    </tbody>
					                  </table>
					                </td>
					              </tr>
					            </tbody>
					          </table>
					        </div>
					      </div>
					    </div>';
			
			// echo $this->mail->Body;
			$this->mail->Body = $msg;
			$this->mail->IsHTML(true);

			if(!$this->mail->send()) {
			    return false;
			} else {
			    return true;
			}
	}

	public function newsletter($email) {
		require_once(dirname(__DIR__).'/vendor/phpmailer/phpmailer/PHPMailerAutoload.php');
		// TCP port to DBconect to
		$this->mail = new PHPMailer;
			//$mail->SMTPDebug = 3;                               // Enable verbose debug output
			$this->mail->isSMTP();                                      // Set mailer to use SMTP
			//add these codes if not written
			$this->mail->IsSMTP();
			$this->mail->SMTPAuth   = true;  
			$this->mail->Host = 'smtp.gmail.com';  // Specify main and backup SMTP servers
			$this->mail->SMTPAuth = true;                               // Enable SMTP authentication
			$this->mail->Username = SMTP_USER;                 // SMTP username
			$this->mail->Password = SMTP_PASSWORD;                           // SMTP password
			$this->mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
			$this->mail->Port = 25;
			$this->mail->setFrom('debexstore.com', 'Debex store  - Verification Email');
			
			$this->mail->addAddress($email, ''); 
       // Add attachments
			    // Optional name
			$this->mail->isHTML(true);                                  // Set email format to HTML

			$this->mail->Subject = 'Debex store News Letter';
			$this->mail->From = "debexstore.com";
			$this->mail->FromName = "debexstore";
			$this->mail->Subject = "Debex store  - News Letter";
			$msg = '<html>
					  <head>
					    <title>News Letter</title>
					  </head>
					  <body>  
					    <div id="" style="display: block;" class="rl-cache-class b-text-part html">
					      <div data-x-div-type="html">
					        <div data-x-div-type="body" style="-webkit-text-size-adjust: none;-ms-text-size-adjust: 100%;background-color: #F3F3F3">
					          <table cellspacing="0" cellpadding="0" border="0" width="100%">
					            <tbody>
					              <tr> 
					                <td style="background-color: #F3F3F3" align="center" valign="top"> 
					                  <table cellspacing="0" cellpadding="0" border="0" width="580"> 
					                    <tbody>
					                      <tr>
					                        
					                      </tr> 
					                      <tr> 
					                        <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 15px;line-height: 19px;font-weight: bold;background-color: #FFFFFF" align="center" width="580"> 
					                          <div>
					                            <div>
					                              <a style="outline: none" target="_blank" tabindex="-1"></a>
					                            </div>
					                          </div> 
					                        </td> 
					                      </tr> 
					                      <tr> 
					                        <td style="padding-top: 30px;padding-right: 40px;padding-bottom: 30px;padding-left: 40px;background-color: #FFFFFF" align="center" width="580"> 
					                          <table cellspacing="0" margin cellpadding="0" border="0" width="580"> 
					                            <tbody>
					                              <tr>
					                              </tr> 
					                              <tr> 
					                                <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 15px;line-height: 19px;font-weight: bold;background-color: #FFFFFF" align="center" width="580"> 
					                                  <div>
					                                    <div>
					                                      <a style="outline: none" target="_blank" tabindex="-1">
					                                      </a>
					                                    </div>
					                                  </div> 
					                                </td> 
					                              </tr> 
					                              <tr> 
					                                <td style="padding-top: 30px;padding-right: 40px;padding-bottom: 30px;padding-left: 40px;background-color: #FFFFFF" align="center" width="580"> 
					                                  <table cellspacing="0" cellpadding="0" border="0"> 
					                                    <tbody>
					                                      <tr> 
					                                        <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 23px;line-height: 28px"> 
					                                          <div style="font-size: 23px;color: #313131;font-weight: bold;text-decoration: none">
					                                            <div style="text-align: center">
					                                              <strong>Welcome to Debex Store</strong>
					                                            </div>
					                                          </div> 
					                                        </td> 
					                                      </tr> 
					                                      <tr> 
					                                        <td style="font-size: 10px;line-height: 10px">
					                                          <img alt="" style="display: block" data-x-broken-src="../images/gif.gif" height="10" width="1">
					                                        </td> 
					                                      </tr> 
					                                      <tr> 
					                                        <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 15px;line-height: 19px;color: #2e2e31" align="left"> 
					                                          <div> 
					                                            <div> 
					                                            </div> 
					                                            <div style="text-align: center">Click the link Below to activate your account.
					                                               
					                                              <center>This is an automated message. Do not reply.</center><br> 
					                                              
					                                            </div> 
					                                          </div> 
					                                        </td> 
					                                      </tr> 
					                                      <tr> 
					                                        <td height="30">
					                                          <img alt="" style="display: block" data-x-broken-src="../images/gif.gif" height="30" width="1">
					                                        </td> 
					                                      </tr> 
					                                      <tr> 
					                                        <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 15px;font-weight: bold" align="center"> 
					                                          <div> 
					                                            <div>
					                                            <p>message test test test</p>  
					                                            
					                                            </div> 
					                                            <div> 
					                                            </div> 
					                                          </div> 
					                                        </td> 
					                                      </tr> 
					                                    </tbody>
					                                  </table> 
					                                </td> 
					                              </tr> 
					                              <tr> 
					                                <td style="font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;font-size: 15px;line-height: 19px;font-weight: bold;background-color: #FFFFFF" align="center" width="580"> 
					                                  <div>
					                                    
					                                  </div> 
					                                </td> 
					                              </tr> 
					                              <tr> 
					                                <td style="background-color: #4C4C4C"> 
					                                  <table style="background-color: #4C4C4C" cellspacing="0" cellpadding="0" border="0" align="center">
					                                    <tbody>
					                                      <tr> 
					                                        <td width="15">
					                                          <img alt="" style="display: block" data-x-broken-src="../images/gif.gif" height="1" width="15"></td> 
					                                        <td width="279">
					                                        <img alt="" style="display: block" data-x-broken-src="../images/gif.gif" height="1" width="279"></td> 
					                                      </tr>
					                                    </tbody>
					                                  </table> 
					                                </td> 
					                              </tr> 
					                              <tr> 
					                                <td style="padding-top: 30px;padding-right: 30px;padding-bottom: 30px;padding-left: 30px"> 
					                                  <table cellspacing="0" cellpadding="0" border="0" align="center"> 
					                                    <tbody>
					                                      <tr> 
					                                        <td style="font-family: "Helvetica Neue", Arial, Helvetica, sans-serif;color: #808285;font-size: 13px;line-height: 19px;font-weight: bold" align="center" valign="top">
					                                          <a href="http://CSEVOTE.LMU.EDU.NG" target="_blank" style="color: #808285;text-decoration: none" rel="external nofollow noopener noreferrer" tabindex="-1">CSE DEV COMMITY</a>
					                                        </td> 
					                                      </tr> 
					                                    </tbody>
					                                  </table> 
					                                </td> 
					                              </tr> 
					                            </tbody>
					                          </table>
					                        </td>
					                      </tr>
					                    </tbody>
					                  </table>
					                </td>
					              </tr>
					            </tbody>
					          </table>
					        </div>
					      </div>
					    </div>';
			
			// echo $this->mail->Body;
			$this->mail->Body = $msg;
			$this->mail->IsHTML(true);

			if(!$this->mail->send()) {
			    return false;
			} else {
			    return true;
			}
	}
}



