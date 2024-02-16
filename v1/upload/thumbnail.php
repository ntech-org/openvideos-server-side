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

if (!isset($_POST["videoId"]) || !isset($_FILES["thumbnailFile"])) {
    http_response_code(400);
    echo json_encode(['message' => 'Missing required parameters.']);
    exit();
}

$videoId = intval($_POST["videoId"]);

$video = Video::getVideo($videoId);

if (!$video) {
    http_response_code(404);
    echo json_encode(['message' => 'Video not found.']);
    exit();
}

if ($video['creatorId'] != $user['id']) {
    http_response_code(403);
    echo json_encode(['message' => 'You are not authorized to perform this action.']);
    exit();
}

// Upload thumbnail

$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/upload/';
$thumbnailUploadDir = $uploadDir . '/thumbnails/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir);
}

if (!is_dir($thumbnailUploadDir)) {
    mkdir($thumbnailUploadDir);
}

$thumbnailPath = $_SERVER["DOCUMENT_ROOT"] . $video["thumbnailUrl"];

if (file_exists($thumbnailPath)) {
    unlink($thumbnailPath);
}

// check if its a real image

if (!getimagesize($_FILES["thumbnailFile"]["tmp_name"])) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid image file.']);
    exit();
}

// check size

if ($_FILES["thumbnailFile"]["size"] > 5000000) {
    http_response_code(400);
    echo json_encode(['message' => 'Thumbnail file is too large. Max size is 5MiB.']);
    exit();
}

// check file type

if ($_FILES["thumbnailFile"]["type"] != "image/jpeg" && $_FILES["thumbnailFile"]["type"] != "image/png") {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid image file.']);
    exit();
}

$ext = pathinfo($_FILES["thumbnailFile"]["name"], PATHINFO_EXTENSION);

// just cuz its a good idea matching the extension
str_replace("jpg", $ext, $thumbnailPath);

if (move_uploaded_file($_FILES["thumbnailFile"]["tmp_name"], $thumbnailPath)) {
    Video::updateVideo($videoId, "thumbnailUrl", str_replace($_SERVER["DOCUMENT_ROOT"], '', $thumbnailPath));
    http_response_code(200);
    echo json_encode(['message' => 'Thumbnail uploaded successfully.', "success" => true]);
    exit();
} else {
    http_response_code(500);
    echo json_encode(['message' => 'Failed to upload thumbnail.']);
    exit();
}