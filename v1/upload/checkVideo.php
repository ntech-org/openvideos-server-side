<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

header('Content-Type: application/json');

if (!isset($_POST['token'])) {
    http_response_code(400);
    echo json_encode(['message' => 'No token provided.']);
    exit();
}

$token = Auth::validateToken($_POST['token']);

if (!$token) {
    http_response_code(401);
    echo json_encode(['message' => 'Invalid token.']);
    exit();
}

$user = User::getUser($token['userId']);

if (isset($_POST["id"])) {
    $id = intval($_POST["id"]);

    $video = Video::getVideo($id);

    if (!$video) {
        http_response_code(404);
        echo json_encode(['message' => 'Video not found.']);
        exit();
    }

    echo json_encode(["uploadStatus" => $video["uploadStatus"]]);
    exit();
}