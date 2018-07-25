<?php
    require('./../util/functions.php');
    $mailing = new Mailing();
    $utility = new Utility();
    $email = $utility->clean_input($_POST['email']);
    if($utility->validate_email($email)) {
        $fields = array('email');
        $values = array($email);
        if($utility->isExist($email, 'subcriptions') == null){
            if($mailing->newsletter($email) == true){
                if($utility->insert('subcriptions', $fields,  $values) == true){
                    echo "you have successfuly subscribed for our News letter";
                }else{
                    echo "error subscribing";
                }
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