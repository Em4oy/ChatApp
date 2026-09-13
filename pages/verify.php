<?php
session_start();
require_once __DIR__ . '/../php/config.php';

$code   = trim($_GET['code']   ?? '');
$status = trim($_GET['status'] ?? '');

// If a raw code arrives (direct link click), hand off to the processing endpoint
if ($code !== '' && $status === '') {
	header('Location: ' . BASE_PATH . 'php/verify.php?code=' . urlencode($code));
	exit;
}

// If already logged in and verified, go straight to chat
if ($status === 'ok' && isset($_SESSION['unique_id'])) {
	header('Location: ' . BASE_PATH . 'chat');
	exit;
}

View::display('verify', ['status' => $status]);
