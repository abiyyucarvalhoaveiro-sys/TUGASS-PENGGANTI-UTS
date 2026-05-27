<?php
session_start();

require_once '../src/Auth.php';
require_once '../config/Database.php';

Auth::check();

$pdo = Database::connect();

$stmt = $pdo->query("
    SELECT * FROM dosen
    WHERE deleted_at IS NOT NULL
");

$data = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Trash</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container">

<h1>Trash Data</h1>

<a href="index.php">Kembali</a>

<table>

<tr>
    <th>NIDN</th>
    <th>Nama</th>
    <th>Email</th>
    <th>Aksi</th>
</tr>

<?php foreach($data as $d): ?>

<tr>
    <td><?= $d['nidn'] ?></td>
    <td><?= $d['nama'] ?></td>
    <td><?= $d['email'] ?></td>
    <td>
        <a href="restore.php?id=<?= $d['id'] ?>">
            Restore
        </a>
    </td>
</tr>

<?php endforeach; ?>

</table>

</div>

</body>
</html>