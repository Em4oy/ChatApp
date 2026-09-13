<?php
session_start();
require_once __DIR__ . '/config.php';

$auth = new Auth($conn);

$input = [
    'fname' => $_POST['fname'] ?? '',
    'lname' => $_POST['lname'] ?? '',
    'email' => $_POST['email'] ?? '',
    'password' => $_POST['password'] ?? ''
];

$result = $auth->register($input, $_FILES ?? null);
if ($result['success']) {
    // Do NOT set session yet — user must verify email first
    echo 'verify';
} else {
    echo $result['message'] ?? 'Registration failed';
}

?>
