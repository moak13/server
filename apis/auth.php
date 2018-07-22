<?php

$app->get('/session', function() {
    $db = new DbHandler();
    $session = $db->getSession();
    $response["_id"] = $session['_id'];
    $response["username"] = $session['username'];
    $response["email"] = $session['email'];
    $response["firstname"] = $session['firstname'];
    $response["lastname"] = $session['lastname'];
    $response["createdAt"] = $session['createdAt'];
    echoResponse(200, $session);
});

$app->post('/login', function() use ($app) {
    require_once 'passwordHash.php';
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('username', 'password'),$r);
    $response = array();
    $db = new DbHandler();

    $matnum = $r->matricnumber;
    $r->matnum = $matnum;
    $password = $r->password;
    $r->secret_key = $password;

    $user = $db->getOneRecord("select _id,fullname, email, secret_key, matnum from peanuts where matnum='$matnum'");
    if ($user != NULL) {
        if(passwordHash::check_password($user['secret_key'],$password)){
            $response['status'] = "success";
            $response['message'] = 'Login was successful';
            $response['_id'] = $user['_id'];
            $response['username'] = $user['username'];
            $response['email'] = $user['email'];
            $response['matnum'] = $user['matnum'];

            if (!isset($_SESSION)) {
                session_start();
            }

            $_SESSION['_id'] = $user['_id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['matnum'] = $user['matnum'];

            echoResponse(200, $response);
        } else {
            $response['status'] = "error";
            $response['message'] = 'Login failed. Incorrect credentials';
            echoResponse(201, $response);
        }
    }else {
            $response['status'] = "error";
            $response['message'] = 'No such user is registered';
            echoResponse(201, $response);
        }

});

$app->get('/logout', function() {
    $db = new DbHandler();
    $session = $db->destroySession();
    $response["status"] = "info";
    $response["message"] = "Logged out successfully";
    echoResponse(200, $response);
});

?>
