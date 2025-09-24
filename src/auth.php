<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $dev_mode = getenv('RWDD_DEV') === '1'; // set RWDD_DEV=1 in your dev env
    if ($dev_mode && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1'], true)) {
        $_SESSION['username'] = 'dev_user';
        $_SESSION['role']     = 'user';
        $_SESSION['loggedin'] = true;
    }
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php'); exit;
}
