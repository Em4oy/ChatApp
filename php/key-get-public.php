<?php
session_start();
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');

$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
if ($userId <= 0) { http_response_code(400); echo 'invalid'; exit; }

$pubPath = __DIR__ . '/../data/keys/' . $userId . '.pub.pem';
if (!file_exists($pubPath)) { http_response_code(404); echo 'notfound'; exit; }
readfile($pubPath);

?>