<?php

session_start();

require_once '../src/Auth.php';
require_once '../config/Database.php';
require_once '../src/Csrf.php';

Auth::check();

$pdo = Database::connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) die('CSRF invalid');

    $nidn = $_POST['nidn'];
    $nama = $_POST['nama'];
    $email = $_POST['email'];

    $foto = '';

    if (!empty($_FILES['foto']['name'])) {

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        $mime = finfo_file(
            $finfo,
            $_FILES['foto']['tmp_name']
        );

        $allowed = [
            'image/jpeg',
            'image/png'
        ];

        if (!in_array($mime, $allowed)) {
            die('File harus JPG atau PNG');
        }

        $ext = pathinfo(
            $_FILES['foto']['name'],
            PATHINFO_EXTENSION
        );

        $foto = sha1(
            uniqid()
        ) . '.' . $ext;

        move_uploaded_file(
            $_FILES['foto']['tmp_name'],
            "../uploads/$foto"
        );
    }

    $stmt = $pdo->prepare("
        INSERT INTO dosen(
            nidn,
            nama,
            email,
            foto
        )
        VALUES(
            ?,
            ?,
            ?,
            ?
        )
    ");

    $stmt->execute([
        $nidn,
        $nama,
        $email,
        $foto
    ]);

    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Tambah Dosen</title>

<link
    rel=\"stylesheet\"
    href=\"assets/style.css\"
>

</head>
<body>

<div class=\"container\">

<h1>Tambah Dosen</h1>

<form
    method=\"POST\"
    enctype=\"multipart/form-data\"
>

<input
    type=\"text\"
    name=\"nidn\"
    placeholder=\"NIDN\"
    required
>

<input
    type=\"text\"
    name=\"nama\"
    placeholder=\"Nama\"
    required
>

<input
    type=\"email\"
    name=\"email\"
    placeholder=\"Email\"
    required
>

<input
    type=\"file\"
    name=\"foto\"
    required
>

<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

<button type=\"submit\">
    Simpan
</button>

</form>

</div>

</body>
</html>