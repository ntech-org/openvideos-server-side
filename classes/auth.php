<?php

class Auth
{
    public static function login($username, $password)
    {
        global $pdo;

        $query = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $query->execute([$username]);
        $user = $query->fetch();
        if ($user == null) {
            return false;
        }
        if (password_verify($password, $user["password"])) {
            $token = self::createToken($user["id"]);

            $_SESSION["user"] = array(
                "id" => $user["id"],
                "username" => $user["username"],
                "admin" => $user["adminLevel"],
                "token" => $token
            );

            setcookie(".NOVSECURITY", $token, strtotime("+1 month"), "/");
            return $token;
        } else {
            return false;
        }
    }

    public static function loginWithToken($token)
    {
        global $pdo;

        $query = $pdo->prepare("SELECT * FROM tokens WHERE token = ?");
        $query->execute([$token]);
        $token = $query->fetch();

        if (!$token) {
            return false;
        }

        if ($token["expiresAt"] < time()) {
            return false;
        }

        $query = $pdo->prepare("SELECT * FROM users WHERE 'id' = ?");
        $query->execute([$token["userId"]]);
        $user = $query->fetch();


        $_SESSION["user"] = array(
            "id" => $user["id"],
            "username" => $user["username"],
            "admin" => $user["adminLevel"],
            "token" => $token["token"]
        );

        $stmt = $pdo->prepare("UPDATE tokens SET expiresAt = :expiresAt WHERE token = :token");
        $stmt->execute([
            'expiresAt' => strtotime("+1 month"),
            'token' => $token["token"]
        ]);

        setcookie(".NOVSECURITY", $token["token"], strtotime("+1 month"), "/");
    }

    public static function createToken($userid)
    {
        global $pdo;

        $token = bin2hex(random_bytes(32));

        $query = $pdo->prepare("INSERT INTO tokens (token, userId, expiresAt, createdAt) VALUES (:token, :userid, :expiresAt, :createdAt)");
        $query->execute(['token' => $token, 'userid' => $userid, 'expiresAt' => strtotime("+1 month"), 'createdAt' => time()]);
        return $token;
    }

    public static function validateToken($token)
    {
        global $pdo;

        $query = $pdo->prepare("SELECT * FROM tokens WHERE token = ?");
        $query->execute([$token]);
        return $query->fetch();
    }

    public static function logout()
    {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM tokens WHERE token = ?");
        $stmt->execute([$_SESSION["user"]["token"]]);

        unset($_SESSION["user"]);
        setcookie(".NOVSECURITY", "", time() - 60 * 60 * 24 * 30, "/");
    }

    public static function isLoggedIn()
    {
        return isset($_SESSION["user"]);
    }

    public static function register($username, $password, $gender)
    {
        global $pdo;

        $query = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $query->execute([$username]);
        $user = $query->fetch();

        if ($user) {
            return "usernameTaken";
        }

        $hashedPassword = password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 2048, 'time_cost' => 4, 'threads' => 3]);

        $query = $pdo->prepare("INSERT INTO users (username, password, gender, createdAt) VALUES (:username, :password, :gender, :createdAt)");
        $action = $query->execute(['username' => $username, 'password' => $hashedPassword, 'gender' => $gender, 'createdAt' => time()]);
        if ($action) {
            return self::login($username, $password);
        } else {
            return false;
        }
    }
}
