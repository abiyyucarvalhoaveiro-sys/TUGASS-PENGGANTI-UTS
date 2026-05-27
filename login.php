<?php
session_start();

require_once '../src/Auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (Auth::login($username, $password)) {

        header('Location: index.php');
        exit;
    }

    $error = 'Login gagal';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container">

    <h1>Login</h1>

    <?php if($error): ?>
        <p><?= $error ?></p>
    <?php endif; ?>

    <form method="POST">

        <input type="text" name="username" placeholder="Username">

        <input type="password" name="password" placeholder="Password">

        <button type="submit">Login</button>

    </form>

</div>

</body>
</html>