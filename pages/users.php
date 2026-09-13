<?php
session_start();
require_once __DIR__ . '/../php/config.php';
if (!isset($_SESSION['unique_id'])) {
    header('location: /login');
    exit;
}

$userModel = new User($conn);
$row = $userModel->getByUniqueId((int) $_SESSION['unique_id']);

View::display('users', ['row' => $row]);

?>