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

$finalFilePath = $argv[1];
$id = $argv[2];
$randomName = $argv[3];
$videoUploadDir = $argv[4];

Video::updateVideo($id, "uploadStatus", "processing");

$resolutions = array(
    '144p' => '256x144',
    '240p' => '426x240',
    '360p' => '640x360',
    '480p' => '854x480',
    '720p' => '1280x720',
    '1080p' => '1920x1080'
);

$vidBasePath = str_replace("/var/www/api-openvideos", '', $videoUploadDir . $randomName . "-");

Video::updateVideo($id, "videoUrl", $vidBasePath);

$commands = array();
foreach ($resolutions as $resolution => $size) {
    $outputVideo = $videoUploadDir . $randomName . "-" . $resolution . '.mp4';
    // Utilize H.265/HEVC codec for better compression efficiency
    $command = "ffmpeg -hwaccel auto -i $finalFilePath -vf scale=$size -c:v libx265 -preset medium -crf 23 -c:a aac -b:a 128k $outputVideo";
    $commands[] = $command;
}

// the thumbnail generator
if ($video["thumbnailUrl"] == '') {
    $thumbnailUploadDir = str_replace("/videos/", '/thumbnails/', $videoUploadDir);
    $command = "ffmpeg -i $finalFilePath -ss 00:00:01.000 -vframes 1 " . $thumbnailUploadDir . $randomName . "-thumbnail.jpg";
    $commands[] = $command;
}

// Execute commands in parallel to utilize multiple CPU cores
foreach ($commands as $command) {
    exec($command . ' > /dev/null 2>&1 &');
}

// Wait for all processes related to the current video to finish
$runningProcesses = true;
while ($runningProcesses) {
    // Get a list of all running ffmpeg processes
    $processesOutput = exec('ps aux | grep "[f]fmpeg -i ' . $finalFilePath . '"');
    // Check if any of the processes correspond to the current video's input file path
    if (empty($processesOutput)) {
        $runningProcesses = false; // No matching processes found, stop waiting
    }
    // Sleep for a short interval before checking again
    sleep(1);
}


// Remove the original video to save space since only the resolution files are needed
if (file_exists($finalFilePath)) {
    unlink($finalFilePath);
}

$thumbBasePath = str_replace("/var/www/api-openvideos", '', $thumbnailUploadDir . $randomName . "-thumbnail.jpg");
Video::updateVideo($id, "thumbnailUrl", $thumbBasePath);
Video::updateVideo($id, "uploadStatus", "finished");
