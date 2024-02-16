<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST["token"])) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid request"));
        exit;
    }
    if (empty($_POST["token"])) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid token"));
        exit;
    }
    Auth::loginWithToken($_POST["token"]);
    Auth::logout();
    echo json_encode(array("message" => "Logged out successfully"));
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Invalid request"));
}