<?php
session_start();
if (isset($_SESSION['unique_id'])) {
    require_once __DIR__ . '/php/config.php';
    header('Location: ' . BASE_PATH . 'chat');
    exit;
}

require_once __DIR__ . '/php/config.php';
// Show the login page at the application root instead of the signup form
View::display('login');

?>
