<?php

class Invite {
    public static function generateInviteKey() {
        global $pdo;

        $key = bin2hex(random_bytes(16));
        
        $query = $pdo->prepare("INSERT INTO invitekeys (invKey, createdAt, createdBy) VALUES (:invKey, :createdAt, :createdBy)");
        $query->execute(['invKey' => $key, 'createdAt' => time(), 'createdBy' => $_SESSION["user"]["id"]]);
        
        return $key;
    }

    public static function validateInviteKey($key) {
        global $pdo;

        $query = $pdo->prepare("SELECT * FROM invitekeys WHERE invKey = :invKey");
        $query->execute(['invKey' => $key]);

        return $query->fetch();
    }

    public static function deleteInviteKey($key) {
        global $pdo;

        $query = $pdo->prepare("DELETE FROM invitekeys WHERE invKey = :invKey");
        $query->execute(['invKey' => $key]);

        return true;
    }
}