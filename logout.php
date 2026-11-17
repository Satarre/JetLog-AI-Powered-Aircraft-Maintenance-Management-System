<?php

require_once __DIR__ . '/includes/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

verify_csrf();
logout_user();

header('Location: ' . url('login.php'));
exit;
