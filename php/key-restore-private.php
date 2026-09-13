<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['unique_id'])) {
	http_response_code(403);
	echo 'Not authenticated';
	exit;
}

$uid      = (int) $_SESSION['unique_id'];
$privPath = __DIR__ . '/../data/keys/' . $uid . '.priv.b64';

if (!file_exists($privPath)) {
	http_response_code(404);
	echo 'not found';
	exit;
}

$priv = trim(file_get_contents($privPath));
if ($priv === '') {
	http_response_code(404);
	echo 'not found';
	exit;
}

header('Content-Type: text/plain; charset=utf-8');
echo $priv;
