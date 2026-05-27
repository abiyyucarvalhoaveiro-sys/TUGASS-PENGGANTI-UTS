<?php
session_start();

require_once '../src/Auth.php';

Auth::logout();

header('Location: login.php');