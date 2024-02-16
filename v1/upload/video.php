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

$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/upload/';
$videoUploadDir = $uploadDir . '/videos/';
$chunkDir = $uploadDir . '/chunks/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir);
}

if (!is_dir($videoUploadDir)) {
    mkdir($videoUploadDir);
}

if (!is_dir($chunkDir)) {
    mkdir($chunkDir);
}

// function to merge all chunks into a single file
function mergeChunks($fileName, $totalChunks, $chunkDir, $videoUploadDir)
{
    $finalFilePath = $videoUploadDir . $fileName;

    $finalFile = fopen($finalFilePath, 'ab');

    for ($i = 0; $i < $totalChunks; $i++) {
        $chunkFileName = $fileName . '.part_' . $i;
        $chunkFilePath = $chunkDir . $chunkFileName;
        $chunkContent = file_get_contents($chunkFilePath);
        fwrite($finalFile, $chunkContent);
        unlink($chunkFilePath); // remove the chunk after merging
    }

    fclose($finalFile);

    return $finalFilePath;
}

// cool check to see if its a chunked upload or just the first prep request
$isChunkedUpload = isset($_POST['chunkNumber']) && isset($_POST['totalChunks']) && isset($_POST["videoId"]);
$isFirstAction = isset($_POST["title"]);
$isFinishAction = isset($_POST["videoId"]) && isset($_POST["finishUpload"]);

if ($isFirstAction && !$isChunkedUpload && !$isFinishAction) {
    // Upload prep
    $title = $_POST["title"];
    $video = Video::createVideo($_POST['title'], $_POST['description'], $user['id']); // lets create the video first

    $randomName = bin2hex(openssl_random_pseudo_bytes(10));

    if ($video) {        
        Video::updateVideo($video, "videoUrl", $randomName);
        Video::updateVideo($video, "uploadStatus", "prepforupload");
        // Upload prep success
        http_response_code(200);
        echo json_encode(['message' => 'Successfully prepared the video upload. Please begin uploading chunks.', 'videoId' => $video]);
        exit();
    } else {
        // Upload prep failed
        http_response_code(500);
        echo json_encode(['message' => 'Failed to prepare upload.']);
        exit();
    }
}

if ($isChunkedUpload && !$isFirstAction && !$isFinishAction) {

    $chunkNumber = intval($_POST['chunkNumber']);
    $totalChunks = intval($_POST['totalChunks']);
    $id = intval($_POST['videoId']);

    $video = Video::getVideo($id);

    if (!$video) { // wth how couldve this happend??? maybe it magically disappeared from the db, who knows
        http_response_code(500);
        echo json_encode(['message' => 'Failed to upload chunk. Video not found.']);
        exit();
    }

    if ($video['creatorId'] != $user['id']) {
        http_response_code(403);
        echo json_encode(['message' => 'You are not authorized to perform this action.']);
        exit();
    }

    Video::updateVideo($id, "uploadStatus", "uploading");

    $fileName = $video['videoUrl'];
    $randomName = $video['videoUrl'];
    $chunkFileName = $randomName . '.part_' . $chunkNumber;
    $chunkFilePath = $chunkDir . $chunkFileName;

    // move received chunk to the appropriate directory
    if (!move_uploaded_file($_FILES['videoFile']['tmp_name'], $chunkFilePath)) {
        http_response_code(500);
        echo json_encode(['message' => 'Failed to upload chunk.']);
        exit();
    }

    if ($chunkNumber == $totalChunks - 1) {
        Video::updateVideo($id, "uploadStatus", "startproccessing");
        http_response_code(200);
        echo json_encode(['message' => 'Successfully uploaded video.', "newstatus" => "startProccessing"]);
        exit;
    }

    http_response_code(200);
    echo json_encode(['message' => 'Chunk uploaded.']);
    exit();
}

if ($isFinishAction) {
    $chunkNumber = intval($_POST['chunkNumber']);
    $totalChunks = intval($_POST['totalChunks']);
    $id = intval($_POST['videoId']);

    $video = Video::getVideo($id);

    if (!$video) { // wth how couldve this happend??? maybe it magically disappeared from the db, who knows
        http_response_code(500);
        echo json_encode(['message' => 'Failed to upload. Video not found.']);
        exit();
    }

    if ($video['creatorId'] != $user['id']) {
        http_response_code(403);
        echo json_encode(['message' => 'You are not authorized to perform this action.']);
        exit();
    }

    $fileName = $video['videoUrl'];
    $randomName = $video['videoUrl'];
    $chunkFileName = $randomName . '.part_' . $chunkNumber;
    $chunkFilePath = $chunkDir . $chunkFileName;

    // if chunks are received merge into the final video file

    $finalFilePath = mergeChunks($fileName, $totalChunks, $chunkDir, $videoUploadDir);

    $command = "nohup php -t " . $_SERVER['DOCUMENT_ROOT'] . " -f " . $_SERVER['DOCUMENT_ROOT'] . "/bg-tasks/video_processing_script.php " . $finalFilePath . " " . $id . " " . $randomName . " " . $videoUploadDir . " > " . $_SERVER['DOCUMENT_ROOT'] . "/bg-tasks/video_processing_script.log 2>&1 &";

    exec($command);

    http_response_code(200);
    echo json_encode(['message' => 'Started processing the video...']);
    exit();
}

function uploadFailedAction($filePath) //unused for now
{
    // Upload failed oopsie
    if (file_exists($filePath)) { // just incase
        unlink($filePath);
    }



    $resolutions = array(
        '144p' => '256x144',
        '240p' => '426x240',
        '360p' => '640x360',
        '480p' => '854x480',
        '720p' => '1280x720',
        '1080p' => '1920x1080'
    );

    $outputPath = $filePath;
    foreach ($resolutions as $resolution => $size) {
        $outputVideo = $outputPath . $resolution . '.mp4';
        if (file_exists($outputVideo)) {
            unlink($outputVideo);
        }
    }

    http_response_code(500);
    echo json_encode(['message' => 'Failed to upload video.']);
    exit();
}
