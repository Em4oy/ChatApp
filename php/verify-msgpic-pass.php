<?php
session_start();
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');
if (!isset($_SESSION['unique_id'])) {
    echo 'invalid';
    exit;
}
// allow verifying against a specific user's MsgPicPassword (sender)
$uid = isset($_POST['user_id']) ? (int) $_POST['user_id'] : (int) $_SESSION['unique_id'];
$password = $_POST['password'] ?? '';
if ($password === '') { echo 'invalid'; exit; }
$stmt = $conn->prepare('SELECT password, end_time FROM msg_pic_passw WHERE user_id = ? LIMIT 1');
if (!$stmt) { echo 'invalid'; exit; }
$stmt->bind_param('i', $uid);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
if (!$row) { echo 'invalid'; exit; }
$stored = $row['password'];
$end_time = isset($row['end_time']) ? (int)$row['end_time'] : 0;
$now = time();
if ($end_time !== 0 && $now > $end_time) { echo 'invalid'; exit; }
if (password_verify($password, $stored)) {
    echo 'success';
} else {
    echo 'invalid';
}
?>