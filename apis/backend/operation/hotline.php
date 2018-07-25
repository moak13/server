<?php
    require('./../util/functions.php');
    $mailing = new Mailing();
    $utility = new Utility();
    $name = $utility->clean_input($_POST['contactname']);
    $email = $utility->clean_input($_POST['contactemail']);
    $message = $utility->clean_input($_POST['contactmessage']);
    if($utility->validate_email($email)) {
        $fields = array('email');
        $values = array($email);
        if($utility->isExist($email, 'subcriptions') == null){
            if($mailing->hotline($email, $message, $name) == true){
                echo "your mail has been dillivered";
            }else{
                echo "error sending email";
            }
        }else{
            echo "this email has subscibed already";
        }
    }else {
        echo "Wrong email format";
    }
?>