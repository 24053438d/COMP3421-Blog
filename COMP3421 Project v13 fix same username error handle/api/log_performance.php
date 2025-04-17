<?php
// Set headers to allow CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include database and analytics classes
require_once '../config/database.php';
require_once '../classes/Analytics.php';

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Make sure required data is provided
if (!isset($data->pageUrl) || !isset($data->loadTime)) {
    http_response_code(400);
    echo json_encode(["message" => "Missing required parameters"]);
    exit();
}

// Sanitize inputs
$pageUrl = htmlspecialchars(strip_tags($data->pageUrl));
$loadTime = floatval($data->loadTime);

// Make sure load time is a reasonable value
if ($loadTime <= 0 || $loadTime > 60) { // Assume any load time over 60 seconds is an error
    http_response_code(400);
    echo json_encode(["message" => "Invalid load time value"]);
    exit();
}

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Create analytics object
$analytics = new Analytics($db);

// Log the performance data
if ($analytics->logPerformance($pageUrl, $loadTime)) {
    http_response_code(201);
    echo json_encode(["message" => "Performance data was logged"]);
} else {
    http_response_code(503);
    echo json_encode(["message" => "Unable to log performance data"]);
}
?> 