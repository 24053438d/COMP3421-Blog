<?php
require_once 'config/database.php';
require_once 'classes/User.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$user->logout();

header("Location: login.php");
exit;
?> 