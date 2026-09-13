<?php
session_start();
if (isset($_SESSION['unique_id'])) {
    require_once __DIR__ . '/../php/config.php';
    header('Location: ' . BASE_PATH . 'chat');
    exit;
}

require_once __DIR__ . '/../php/config.php';
// Show the signup page
View::display('index');

?>