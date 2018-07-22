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

        public function CreateUser($name, $email, $matnum, $username, $password)
        {
           if(!$this->isEmailExist($email))
           {
                $stmt = $this->conn->prepare("INSERT INTO users (name, email, matnum, username, password) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $name, $email, $matnum, $username, $password);
                if($stmt->execute())
                {
                    return USER_CREATED;
                }else {
                    return USER_FAILED;
                }
           }
           return USER_EXISTS;
        }

        public function userLogin($email, $username, $password)
        {
            require_once dirname(__FILE__) . '/passwordHash.php';
            if($this->isEmailExist($email))
            {
                $secret_key = $this->getUserPassword($email, $username);
                if(passwordHash::check_password($secret_key, $password))
                {
                    if(!isset($_SESSION))
                    {
                        session_start();
                    }
                    $_SESSION['id'] = 'id';
                    return USER_VERIFIED;
                }else {
                    return USER_PASS_ERR;
                }
            }else {
                return USER_NULL;
            }
        }

        private function getUserPassword($data, $password)
        {
            $stmt = $this->conn->prepare("SELECT email, username, password FROM users WHERE 
            email = $data OR username = $data");
            $stmt->execute();
            $stmt->bind_result($password);
            $stmt->fetch();
            return $password;
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
                $sess["createdAt"] = $_SESSION['createdAt'];
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
                unset($_SESSION['createdAt']);
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

       /* private function getUserPasswordByEmail($email)
        {
            $stmt = $this->conn->prepare("SELECT password FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($password);
            $stmt->fetch();
            return $password;
        }

        private function getUserPasswordByUsername($username)
        {
            $stmt = $this->conn->prepare("SELECT password FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->bind_result($password);
            $stmt->fetch();
            return $password;
        }*/

        private function isEmailExist($email)
        {
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_results();
            return $stmt->num_rows > 0;
        }

        private function isUsernameExist($username)
        {
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_results();
            return $stmt->num_rows > 0;
        }


    }