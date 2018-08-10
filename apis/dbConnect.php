<?php

class DbConnect {

    private $conn;


    function __construct() {
    }

    function connect() {
        require_once('./../conf/config.php');
        $server = DB_HOST;
        $user = DB_USER;
        $db = DB_NAME;
        $pass = DB_PASS;

        try
        {
            $this->conn = new PDO("mysql:host=$server;dbname=$db", $user, $pass);
            // set the PDO error mode to exception
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "success";
        }
        catch(PDOException $e)
        {
            echo "Connection failed: " . $e->getMessage();
        }
        return $this->conn;
    }
}
