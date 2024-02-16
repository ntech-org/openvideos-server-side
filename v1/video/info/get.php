<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $video = Video::getVideo($id);

    echo json_encode(
        array(
            "id" => $video['id'],
            "title" => $video['title'],
            "description" => $video['description'],
            "videoUrl" => "https://api-openvideos.nicolastech.xyz/v1/video/watch?id=" . $video['id'],
            "thumbnailUrl" => "https://api-openvideos.nicolastech.xyz/" . $video['thumbnailUrl'],
            "creatorId" => $video['creatorId'],
            "authorName" => User::getUser($video['creatorId'])["username"],
            "views" => 69,
            
        )
    );
}
