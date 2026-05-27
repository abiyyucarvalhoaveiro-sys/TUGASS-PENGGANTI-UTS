<?php

class Database
{
    private static ?PDO $pdo = null;

    public static function connect(): PDO
    {
        if (self::$pdo === null) {

            $dsn = "mysql:host=localhost;dbname=siakad_mini;charset=utf8mb4";

            self::$pdo = new PDO($dsn, 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        }

        return self::$pdo;
    }
}