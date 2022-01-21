<?php

session_start();
$site = filter_input(INPUT_GET, 'site');
$db_name = 'fullcalendar_demo';
$_SESSION['db_name'] = $db_name;

define('DB_SERVER', 'localhost'); //please enter mysql server name
define('DB_USERNAME', 'root'); // please enter mysql user name
define('DB_PASSWORD', ''); // please enter mysql user password
define('DB_DATABASE', $db_name); // please enter your database name

define('DB_PREFIX', 'acd_');
define('LOGOUT_TIME', 1800);
define('DECIMAL', 2);
define('MIN_COMPONENTS_TO_DISPLAY', 4);
define('TAX_DECIMAL', 5);
define('DATE_FORMAT', "d-m-Y");
define('DEFAULT_CURRENCY', '$');
define('DEFAULT_AREA_UNIT', 'sqmt');
define('FORMATS', "pdf,docx,doc");
define('IMG_FORMATS', "jpg,jpeg,gif,png");
define('DEFAULT_COUNTRY_ID', 'IN');
date_default_timezone_set('Asia/Kolkata');

require_once DOCUMENT_ROOT . "html/class/db.class.php";

$db = new db(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
?>