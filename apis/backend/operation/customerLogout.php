<?php
    require_once ('./../util/functions.php');
    require_once ('./../util/hashing.php');
    $Auth = new Auth();
    session_start();
    if($Auth->logout()){
        
    }
    exit;
?>