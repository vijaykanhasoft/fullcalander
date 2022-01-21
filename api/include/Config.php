<?php
 
$db_name = 'fullcalendar_demo';
 
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_HOST', 'localhost');
define('DATABASE_NAME', $db_name);
define('DATE_FORMAT', "M d, Y");
define('TIME_FORMAT', "h:i a");
define('TIMEZONE', "");
require_once "db.class.php";

$db = new db(DB_HOST, DB_USERNAME, DB_PASSWORD, DATABASE_NAME);
