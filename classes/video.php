<?php

class Video
{
    public static function getVideo($id)
    {
        global $pdo;

        $stmt = $pdo->prepare("SELECT * FROM videos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function getVideos()
    {
        global $pdo;

        $stmt = $pdo->prepare("SELECT * FROM videos");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getVideosByUser($userid)
    {
        global $pdo;

        $stmt = $pdo->prepare("SELECT * FROM videos WHERE creatorId = ?");
        $stmt->execute([$userid]);
        return $stmt->fetchAll();
    }

    public static function deleteVideo($id)
    {
        global $pdo;

        $stmt = $pdo->prepare("DELETE FROM videos WHERE id = ?");
        $stmt->execute([$id]);
    }

    public static function createVideo($title, $description, $creatorid)
    {
        global $pdo;

        $stmt = $pdo->prepare("INSERT INTO videos (title, description, creatorId, createdAt) VALUES (:title, :description, :creatorid, :createdAt)");
        $stmt->execute(['title' => $title, 'description' => $description, 'creatorid' => $creatorid, 'createdAt' => time()]);
        return $pdo->lastInsertId();
    }

    public static function updateVideo($id, $key, $value)
    {
        global $pdo;

        $stmt = $pdo->prepare("UPDATE videos SET $key = ? WHERE id = ?");
        $stmt->execute([$value, $id]);
    }
}
