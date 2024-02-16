<?php

session_start();

$options = array(
    'DB' => array(
        'host' => '127.0.0.1',
        'user' => 'admin',
        'password' => 'omgUwuUwuNyaImAcuteKitty:3',
        'dbname' => 'openvideos',
    )
);
define("CONFIG", $options);

try {
    $pdo = new PDO("mysql:host=" . CONFIG['DB']['host'] . ";dbname=" . CONFIG['DB']['dbname'], CONFIG['DB']['user'], CONFIG['DB']['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}
//require all the classes
foreach (glob($_SERVER['DOCUMENT_ROOT'] . "/classes/*.php") as $filename) {
    require_once $filename;
}

if ($_SERVER["CONTENT_TYPE"] == "application/json") {
    $_POST = json_decode(file_get_contents('php://input'), true);
}

$request_method = $_SERVER["REQUEST_METHOD"];

# Simple requests
if ($request_method == "GET" || $request_method == "POST") {
    header("Access-Control-Allow-Origin: *");
}

# Preflighted requests
if ($request_method == "OPTIONS") {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    exit();
}
