<?php
// find root directory and user_fn.php to handle logout
define('ROOT', dirname(__DIR__));
// using functions from user_fn.php to handle logout
require_once ROOT . '/function/user_fn.php';
logoutUser();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Location: /system/login_register.php');
exit;