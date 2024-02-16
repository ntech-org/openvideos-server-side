<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST["username"]) || !isset($_POST["password"]) || !isset($_POST["confirmPassword"])) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid request"));
        exit;
    }
    if (empty($_POST["username"]) || empty($_POST["password"]) || empty($_POST["confirmPassword"])) {
        http_response_code(400);
        echo json_encode(array("message" => "Empty fields! Please fill all the fields."));
        exit;
    }

    if ($_POST["password"] !== $_POST["confirmPassword"]) {
        http_response_code(400);
        echo json_encode(array("message" => "Passwords do not match!"));
        exit;
    }

    if (!preg_match('/^[a-zA-Z0-9]+$/', $_POST["username"])) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid username! Only letters and numbers are allowed."));
        exit;
    }

    if (User::getUserByUsername($_POST["username"])) {
        http_response_code(400);
        echo json_encode(array("message" => "Username already exists!"));
        exit;
    }

    if (!Auth::register($_POST["username"], $_POST["password"], "unused")) {
        http_response_code(400);
        echo json_encode(array("message" => "An unknown error occurred during registration."));
        exit;
    }

    $loggedin = Auth::login($_POST["username"], $_POST["password"]);

    if ($loggedin) {
        echo json_encode(array("token" => $loggedin));
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Login procedure failed."));
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Invalid request"));
    exit;
}