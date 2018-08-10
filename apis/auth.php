<?php

    class auth
    {

        private $conn;

        function __construct()
        {
            require_once dirname(__FILE__) . '/DbConnect.php';
            $db = new DbConnect;
            $this->conn = $db->connect();
        }

        public function CreateUser($name, $email, $matnum, $username, $secret_key)
        {
            try{
                if(!$this->isEmailExist($email))
                {
                    $stmt = $this->conn->prepare("INSERT INTO users (`name`, email, matnum, username, `password`) VALUES ('$name', '$email', '$matnum', '$username', '$secret_key')");
                    if($stmt->execute())
                    {
                        return USER_CREATED;
                    }else {
                        return USER_FAIL;
                    }
                }
                else{
                 return USER_EXITS;
                }
            }catch(PDOExeception $ex) {
                echo "PDO did not work";
            }
        }

        public function userLogin($data, $password)
        {
            try {
                require_once dirname(__FILE__) . '/passwordHash.php';
                if($this->isDetailsExist($data))
                {
                    $user = $this->getUserPassword($data, $password);
                    if(passwordHash::check_password($user["password"], $password))
                    {
                        if(!isset($_SESSION))
                        {
                            session_start();
                        }
                        $_SESSION['id'] = $user["id"];
                        $_SESSION['timestamp'] = time();
                        return USER_VERIFIED;
                    }else {
                        return USER_PASS_ERR;
                    }
                }else {
                    return USER_NULL;
                }
            }catch (PDOException $ex) {
                echo "PDO did not work";
            }
        }

        private function isEmailExist($email) {
            try {
                $stmt = $this->conn->prepare("SELECT email FROM users WHERE email = :email");
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
 
        private function isDetailsExist($data)
        {
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email =:details OR username =:details");
            $stmt->execute(array(':details' => $data));
            $stmt->fetch(PDO::FETCH_ASSOC);
            return $stmt->rowCount() > 0;
        }

        private function getUserPassword($data, $password)
        {
            $stmt = $this->conn->prepare("SELECT email, username, password FROM users 
            WHERE email =:userdetails OR username =:userdetails");
            $stmt->bindParam(":userdetails", $data);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row;
        }

        public function getUserByData($data)
        {
            $stmt = $this->conn->prepare("SELECT id, `name`, email, matnum, username FROM users WHERE 
            email =:details OR username =:details");
            $stmt->bindParam(":details", $data);
            $stmt->execute();
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            $user = array();
            if($stmt->rowCount() > 0){
                $user['id'] = $data["id"];
                $user['name'] = $data["name"];
                $user['email'] = $data["email"];
                $user['matnum'] = $data["matnum"];
                $user['username'] = $data["username"];
                return $user;
            }else{
                return NULL;
            }
        }

        public function getSession()
        {
            if(!isset($_SESSION))
            {
                session_start();
            }
            $sess = array();
            if(isset($_SESSION['id']))
            {
                $sess["id"] = $_SESSION['id'];
                $sess["email"] = $_SESSION['email'];
                $sess["username"] = $_SESSION['username'];
                $sess["timestamp"] = $_SESSION['timestamp'];
            }else {
                $sess = "Error";
            }
            return $sess;
        }

        public function destroySession(){
            if (!isset($_SESSION))
            {
                session_start();
            }
            if(isSet($_SESSION['id']))
            {
                unset($_SESSION['id']);
                unset($_SESSION['email']);
                unset($_SESSION['username']);
                unset($_SESSION['timestamp']);
                session_destroy();
                session_unset();
                $info='info';
                if(isSet($_COOKIE[$info]))
                {
                    setcookie ($info, '', time() - $cookie_time);
                }
                $msg = "Logged Out Successfully...";
            }
            else
            {
                $msg = "Not logged in...";
            }
            return $msg;
        }
    }