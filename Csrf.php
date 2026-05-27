<?php

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf'];
    }

    public static function validate(string $token): bool
    {
        return hash_equals($_SESSION['csrf'] ?? '', $token);
    }
}