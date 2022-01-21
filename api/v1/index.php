<?php

header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
session_start();
require_once '../include/Config.php';
require '../libs/Slim/Slim.php';
require '../libs/Slim/Extras/Log/DateTimeFileWriter.php';
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim(
        array(
    'debug' => true,
    'log.enabled' => true,
    'log.level' => \Slim\Log::DEBUG,
    'log.writer' => new \Slim\Extras\Log\DateTimeFileWriter(array(
        'path' => '../logs',
        'name_format' => 'Y-m-d',
        'message_format' => '%label% - %date% - %message%'
            ))
        )
);

function api_autoloader($class_name) {
    $directory = '../include/';
    if (file_exists($directory . $class_name . '.class.php')) {
        require_once $directory . $class_name . '.class.php';
        return;
    }
}

/* BY DEFAULT HERE I SET THE USER DATA WHICH USER ID IS 1 BUT AFTER LOGIN FUNCTIONALITY IT WILL CHANGE */
global $userData;
$userData['user_id'] = 1;
$userData['name'] = 'krunal';

function authenticate(\Slim\Route $route) {
    /* THIS IS FOR AUTHENTICATION CHECK, BUT RIGHT NOW THERE IS NOW AUTHENTICATION , IT IS JUST CONSIDER THE USER COMPUTER TIMEZONE. */
    global $userData;
    $headers = apache_request_headers(); 
    $userData['timezone'] = $headers['timezone'];
    
}


spl_autoload_register('api_autoloader');

// THIS METHOD IS FOR USER LIST WITH THEIR COLOR FOR EVENTS AND TASKS.
$app->get('/calendar_user/:user_id','authenticate' ,function($user_id) {
    try {
        
        $response = array();
        $calendar = new calendar();
        $result = $calendar->getUsers($user_id);
        $response['data'] = $result;
        $response['error'] = false;
        echoRespnse(200, $response);
    } catch (Exception $e) {
        $response["error"] = true;
        $response["message"] = "There is some problem in application";
        $response["org_error"] = $e->getMessage() . " ::File:" . $e->getFile() . " ::Line:" . $e->getLine();
        echoRespnse(404, $response);
    }
});

// THIS METHOD IS FOR GET DATA OF THE CALENDAR BASED ON STARTDATE AND ENDDATE.

$app->get('/calendar', 'authenticate', function() use ($app) {

    try {
        $response = array();
        $calendar = new calendar();
        $res = $app->request->get();
        $start_date = $res['start'];
        $end_date = $res['end'];
        if (isset($_REQUEST['user_id'])) {
            $created_by = $_REQUEST['user_id'];
            if ($created_by !== "") {
                $users = explode(',', $created_by);
            } else {
                $users = "";
            }
            $response = $calendar->getCalendar($users, $res['created_by'], $start_date, $end_date);
        } else {
            $response = $calendar->getCalendar($res['created_by'], $res['created_by'], $start_date, $end_date);
        }
        echoRespnse(200, $response);
    } catch (Exception $e) {
        $response["error"] = true;
        $response["message"] = "There is some problem in application";
        $response["org_error"] = $e->getMessage() . " ::File:" . $e->getFile() . " ::Line:" . $e->getLine();
        echoRespnse(404, $response);
    }
});


// THIS METHOD IS FOR SAVE CALENDAR SETTING (COLOR CHANGE).
$app->post('/calendarSettings', 'authenticate', function() use ($app) {
    try {
        $response = array();
        $calendar_data = $app->request->post();
        $calendar = new calendar();
        $response = $calendar->saveSetings($calendar_data);
        echoRespnse(200, $response);
    } catch (Exception $e) {
        $response["error"] = true;
        $response["message"] = "There is some problem in application";
        $response["org_error"] = $e->getMessage() . " ::File:" . $e->getFile() . " ::Line:" . $e->getLine();
        echoRespnse(404, $response);
    }
});


/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);
    // setting response content type to json
    $app->contentType('application/json');
    if (gettype($response) == 'array') {
        if (array_key_exists('notification', $response))
            unset($response['notification']);
        if (array_key_exists('history', $response))
            unset($response['history']);
    }
    // print_r($response);
    echo json_encode($response);
}

$app->run();
