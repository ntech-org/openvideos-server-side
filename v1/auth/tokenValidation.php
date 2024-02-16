<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST["token"])) {
        http_response_code(401);
        exit("token is missing");
    }
    if (empty($_POST["token"])) {
        http_response_code(401);
        exit("token is empty");
    }
    $loggedin = Auth::validateToken($_POST["token"]);

    if ($loggedin) {
        http_response_code(200);
        echo json_encode(array("token" => $loggedin));
        exit("token is valid");
    } else {
        http_response_code(401);
        exit("token is invalid");
    }
}
http_response_code(500);
exit;