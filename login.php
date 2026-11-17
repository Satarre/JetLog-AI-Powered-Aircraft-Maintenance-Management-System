<?php

require_once __DIR__ . '/includes/init.php';

if (current_user() !== null) {
    redirect('index.php');
}

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = post_string('username');
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $errors[] = 'Enter a username and password.';
    } else {
        $stmt = db()->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            login_user($user);
            flash('success', 'Signed in as ' . $user['full_name'] . '.');
            redirect('index.php');
        }

        $errors[] = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in | JetLog</title>
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>?v=<?= filemtime(__DIR__ . '/assets/css/app.css') ?>">
    <link rel="icon" type="image/png" href="<?= url('assets/img/favicon.png') ?>">
</head>
<body class="login-page">
    <div class="login-box">
        <aside class="login-aside">
            <img class="login-mark" src="<?= url('assets/img/logo.png') ?>" alt="" width="34" height="41" decoding="async">
            <div class="brand-name">JetLog</div>
            <p class="login-sub">Maintenance tracking</p>
            <div class="login-footer">Demo data. Not affiliated with Cathay Pacific.</div>
        </aside>

        <div class="login-main">
            <h1 class="login-title">Log in</h1>

            <?php show_errors($errors); ?>

            <form method="post" action="<?= url('login.php') ?>">
                <?= csrf_field() ?>
                <div class="form-row">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?= e($username) ?>" autocomplete="username" autofocus required>
                </div>
                <div class="form-row">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn">Log in</button>
            </form>

            <div class="login-demo">Demo login: tech / jetlog123</div>
        </div>
    </div>
</body>
</html>
