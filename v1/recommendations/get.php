<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

header("Content-Type: application/json");

$data = Video::getVideos();

$recommendeds = array();

foreach ($data as $video) {
    $recommendeds[] = array(
        "id" => $video['id'],
        "title" => $video['title'],
        "thumbnailUrl" => "https://api-openvideos.nicolastech.xyz" . $video['thumbnailUrl'],
        "authorName" => User::getUser($video['creatorId'])["username"],
        "views" => 69
    );
}


$recommendeds = array(
    "maxPage" => 1,
    "data" => $recommendeds
);

echo json_encode($recommendeds);
