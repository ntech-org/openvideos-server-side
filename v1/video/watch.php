<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/config.php';

header('Content-Type: video/mp4');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $resolution = isset($_GET['resolution']) ? $_GET['resolution'] : '1080p';

    $resolutions = array(
        '144p',
        '240p',
        '360p',
        '480p',
        '720p',
        '1080p',
    );

    if (!in_array($resolution, $resolutions)) {
        $resolution = '1080p';
    }

    $video = Video::getVideo($id);

    // stream the video
    $path = $_SERVER['DOCUMENT_ROOT'] . $video['videoUrl'] . $resolution . ".mp4";

    // use length also to stream different parts of the video
    $range = $_SERVER['HTTP_RANGE'] ?? null;

    if ($range) {
        $range = preg_replace('/bytes=/i', '', $range);
        $range = explode('-', $range);
        $start = $range[0];
        $end = isset($range[1]) ? $range[1] : '';
        $end = ($end !== '') ? $end : filesize($path) - 1;
        $length = $end - $start + 1;

        header('HTTP/1.1 206 Partial Content');
        header('Content-Length: ' . $length);
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . filesize($path));

        // Read only the required content from the file
        $fp = fopen($path, 'rb');
        fseek($fp, $start);
        while (!feof($fp) && ($p = ftell($fp)) <= $end) {
            if ($p + 8192 > $end) {
                // In case the remaining chunk is less than 8192 bytes, adjust the read length
                echo fread($fp, $end - $p + 1);
            } else {
                echo fread($fp, 8192);
            }
            flush();
        }
        fclose($fp);
    } else {
        header('Content-Length: ' . filesize($path));
        readfile($path);
    }
}
