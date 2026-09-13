<?php
session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: text/plain; charset=utf-8');

if (!isset($_SESSION['unique_id'])) {
    echo 'error';
    exit;
}

$unique_id = (int) $_SESSION['unique_id'];
$password = $_POST['password'] ?? '';
if ($password === '') {
    echo 'error';
    exit;
}

$userModel = new User($conn);
$user = $userModel->getByUniqueId($unique_id);
if (!$user) {
    echo 'error';
    exit;
}

$stored = $user['password'];
$valid = false;
if (password_verify($password, $stored)) {
    $valid = true;
} elseif (md5($password) === $stored) {
    $valid = true;
}

echo $valid ? 'success' : 'invalid';

?>