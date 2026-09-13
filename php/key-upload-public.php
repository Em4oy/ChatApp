<?php
session_start();
require_once __DIR__ . '/config.php';
if (!isset($_SESSION['unique_id'])) {
    http_response_code(403);
    echo 'Not authenticated';
    exit;
}
$uid = (int) $_SESSION['unique_id'];
$pub = trim($_POST['public_key'] ?? '');
if ($pub === '') { http_response_code(400); echo 'empty'; exit; }

$dir = __DIR__ . '/../data/keys/';
if (!is_dir($dir)) mkdir($dir, 0700, true);
$pubPath = $dir . $uid . '.pub.pem';
if (file_put_contents($pubPath, $pub) === false) {
    http_response_code(500); echo 'failed'; exit;
}
@chmod($pubPath, 0644);
echo 'ok';
?>