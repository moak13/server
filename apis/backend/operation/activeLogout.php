<?php
    require_once ('./../util/functions.php');
    require_once ('./../util/hashing.php');
    $Auth = new Auth();
    session_start();
    if($Auth->activelogout()){
        echo "loggedOut_successfully";
    }
    exit;
?>