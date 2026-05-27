<?php
session_start();

require_once '../src/Auth.php';
require_once '../config/Database.php';

Auth::check();

$pdo = Database::connect();

$id = $_GET['id'];

$stmt = $pdo->prepare("
    UPDATE dosen
    SET deleted_at = NULL
    WHERE id = :id
");

$stmt->execute([
    'id' => $id
]);

header('Location: trash.php');