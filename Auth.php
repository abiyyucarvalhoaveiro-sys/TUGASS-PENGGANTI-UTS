<?php

require_once __DIR__ . '/../config/Database.php';

class Auth
{
    public static function login($username, $password)
    {
        $pdo = Database::connect();

        $stmt = $pdo->prepare("
            SELECT * FROM users
            WHERE username = :username
        ");

        $stmt->execute([
            'username' => $username
        ]);

        $user = $stmt->fetch();

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];

        return true;
    }

    public static function check()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
    }

    public static function logout()
    {
        session_destroy();
    }
}