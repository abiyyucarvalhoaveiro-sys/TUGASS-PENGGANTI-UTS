<?php

class RateLimiter
{
    public static function hit()
    {
        if (!isset($_SESSION['login_attempt'])) {
            $_SESSION['login_attempt'] = 0;
        }

        $_SESSION['login_attempt']++;

        if ($_SESSION['login_attempt'] > 5) {
            die('Terlalu banyak login');
        }
    }

    public static function clear()
    {
        $_SESSION['login_attempt'] = 0;
    }
}