<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['unique_id'])) {
	http_response_code(403);
	echo 'Not authenticated';
	exit;
}

$uid  = (int) $_SESSION['unique_id'];
$priv = trim($_POST['private_key'] ?? '');

if ($priv === '') {
	http_response_code(400);
	echo 'empty';
	exit;
}

// validate: must be a valid base64 string
if (base64_decode($priv, true) === false) {
	http_response_code(400);
	echo 'invalid';
	exit;
}

$dir     = __DIR__ . '/../data/keys/';
if (!is_dir($dir)) mkdir($dir, 0700, true);
$privPath = $dir . $uid . '.priv.b64';

if (file_put_contents($privPath, $priv) === false) {
	http_response_code(500);
	echo 'failed';
	exit;
}
// restrict to owner-readable only
@chmod($privPath, 0600);
echo 'ok';
