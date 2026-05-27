<?php

session_start();

require_once '../src/Auth.php';
require_once '../config/Database.php';

Auth::check();

$pdo = Database::connect();

$totalDosen = $pdo->query("
    SELECT COUNT(*) FROM dosen
    WHERE deleted_at IS NULL
")->fetchColumn();

$totalAdmin = $pdo->query("
    SELECT COUNT(*) FROM users
    WHERE role = 'admin'
")->fetchColumn();

$totalOperator = $pdo->query("
    SELECT COUNT(*) FROM users
    WHERE role = 'operator'
")->fetchColumn();

?>

<!DOCTYPE html>
<html>
<head>

<title>Dashboard</title>

<link
    rel="stylesheet"
    href="assets/style.css"
>

</head>
<body>

<div class="container">

<h1>Dashboard</h1>

<h3>Total Dosen:
<?= $totalDosen ?>
</h3>

<h3>Total Admin:
<?= $totalAdmin ?>
</h3>

<h3>Total Operator:
<?= $totalOperator ?>
</h3>

<a href="index.php">
    Data Dosen
</a>

</div>

</body>
</html>