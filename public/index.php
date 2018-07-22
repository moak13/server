<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';
require '../apis/DbConnect.php';
require_once '../apis/passwordHash.php';

$app = new \Slim\App([
    'settings'=>[
        'displayErrorDetails'=>true
    ]
]);

$app->post('/signup', function(Request $request, Response $response)
{
    if(!verifyRequiredParams(array('name', 'email', 'matnum', 'username', 'password'), $response))
    {
        $request_data = $request->getParsedBody();

        $name = $request_data['name'];
        $email = $request_data['email'];
        $matnum = $request_data['matnum'];
        $username = $request_data['username'];
        $password = $request_data['password'];

        $secret_key = passwordHash::hash($password);

        $db = new auth;

        $result = $db->createUser($name, $email, $matnum, $username, $secret_key);
        if($result == USER_CREATED)
        {
            $message = array();
            $message['error'] = false;
            $message['message'] = 'User Created!';

            $response->write(json_encode($message));
            return $response->withHeader('Content-type', 'application/json')->withStatus(201);
        }
        else if($result == USER_FAIL)
        {
            $message = array();
            $message['error'] = true;
            $message['message'] = 'Some Error Occured!';

            $response->write(json_encode($message));
            return $response->withHeader('Content-type', 'application/json')->withStatus(422);
        }
        else if($result == USER_EXITS)
        {
            $message = array();
            $message['error'] = true;
            $message['message'] = 'User Already Exits!';

            $response->write(json_encode($message));
            return $response->withHeader('Content-type', 'application/json')->withStatus(422);
        }
    }
    return $response->withHeader('Content-type', 'application/json')->withStatus(422);
});

$app->post('/login', function(Request $request, Response $response)
{

}
);

//check if input field is not empty
function verifyRequiredParams($required_params, $response)
{
    $error = false;
    $error_params = '';
    $request_params = $_REQUEST;

    foreach($required_params as $param)
    {
        if(!isset($request_params[$param]) || strlen(trim($request_params[$param]))<=0)
        {
            $error = true;
            $error_params .= $param . ', ';
        }
    }

    if($error)
    {
        $error_detail = array();
        $error_detail['error'] = true;
        $error_detail['message'] = 'Required parameter(s) ' . substr($error_params, 0, -2) . 'is missing or empty';
        $response->write(json_encode($error_detail));
    }
    return $error;
}

// Run app
$app->run();
