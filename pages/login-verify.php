<?php
session_start();

// If already fully logged in, go to chat
if (isset($_SESSION['unique_id'])) {
	require_once __DIR__ . '/../php/config.php';
	header('Location: ' . BASE_PATH . 'chat');
	exit;
}

// If there is no pending login, bounce back to login page
if (empty($_SESSION['pending_uid'])) {
	require_once __DIR__ . '/../php/config.php';
	header('Location: ' . BASE_PATH . 'login');
	exit;
}

require_once __DIR__ . '/../php/config.php';
View::display('login-verify');
