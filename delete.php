<?php
session_start();

require_once '../src/Auth.php';
require_once '../config/Database.php';

Auth::check();

$pdo = Database::connect();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    exit('CSRF token tidak valid');
}

unset($_SESSION['csrf_token']);

$id = (int)($_POST['id'] ?? 0);

$stmt = $pdo->prepare("
    UPDATE dosen
    SET deleted_at = NOW()
    WHERE id = :id
");

$stmt->execute([
    'id' => $id
]);

header('Location: index.php');