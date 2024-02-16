<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST["username"]) || !isset($_POST["password"])) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid request"));
        exit;
    }
    if (empty($_POST["username"]) || empty($_POST["password"])) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid credentials"));
        exit;
    }
    $loggedin = Auth::login($_POST["username"], $_POST["password"]);

    if ($loggedin) {
        echo json_encode(array("token" => $loggedin));
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Incorrect username or password"));
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Invalid request"));
    exit;
}