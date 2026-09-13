<?php
session_start();
require_once __DIR__ . '/config.php';
if (!isset($_SESSION['unique_id'])) {
    // Always redirect to the friendly /login route (not /php/login)
    header('Location: /login');
    exit;
}

$logout_id = isset($_GET['logout_id']) ? (int) $_GET['logout_id'] : null;
if ($logout_id) {
    $user = new User($conn);
    $user->updateStatus($logout_id, 'Offline');
    session_unset();
    session_destroy();
    // After logout, send user to the top-level /login route
    header('Location: /login');
    exit;
}

header('Location: ' . BASE_PATH . 'users');
?>
