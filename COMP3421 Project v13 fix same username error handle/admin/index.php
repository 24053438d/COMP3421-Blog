<?php
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../middleware/AuthMiddleware.php';

// Ensure the user is an admin
AuthMiddleware::requireAdmin();

// Redirect to dashboard
header("Location: dashboard.php");
exit;
?> 