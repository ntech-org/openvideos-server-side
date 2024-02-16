<?php

// enable errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// require all the classes
foreach (glob("/var/www/api-openvideos/classes/*.php") as $filename) {
    require_once $filename;
}

// Retrieve all videos
$stmt = $pdo->query("SELECT id, thumbnailUrl, videoUrl FROM videos WHERE thumbnailUrl IS NULL OR thumbnailUrl = ''");
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Iterate through videos
foreach ($videos as $video) {
    $id = $video['id'];
    $filePath = "/var/www/api-openvideos" . $video['videoUrl'] . "1080p.mp4";

    $randomName = $video["videoUrl"];
    $randomName = str_replace("//", '/', $randomName);
    $randomName = str_replace("/upload/videos/", '', $randomName);
    $randomName = str_replace("-", "", $randomName);

    // Generate thumbnail if thumbnailUrl is empty
    $thumbnailUploadDir = "/var/www/api-openvideos/upload/thumbnails/";
    $thumbnailPath = $thumbnailUploadDir . $randomName . "-thumbnail.jpg";

    // Check if thumbnail already exists
    if (!file_exists($thumbnailPath)) {
        // Generate thumbnail
        $command = "ffmpeg -i $filePath -ss 00:00:01.000 -vframes 1 $thumbnailPath";
        exec($command);
        
        // Update thumbnailUrl in database
        $thumbBasePath = str_replace("/var/www/api-openvideos", '', $thumbnailPath);
        Video::updateVideo($id, "thumbnailUrl", $thumbBasePath);
    }
}

echo "Thumbnails generated for videos without a thumbnail URL.";

?>
