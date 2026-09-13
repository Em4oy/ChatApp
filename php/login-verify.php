<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/delete-private-messages.php';

// Must have a pending login session
if (empty($_SESSION['pending_uid'])) {
	echo 'expired';
	exit;
}

$uid  = (int) $_SESSION['pending_uid'];
$code = trim($_POST['code'] ?? '');

if ($code === '') {
	echo 'Code is not correct, try again.';
	exit;
}

$now  = time();
$stmt = $conn->prepare(
	"SELECT `login_code`, `end_time` FROM `user_login_code` WHERE `user_id` = ? LIMIT 1"
);

if (!$stmt) {
	echo 'Server error.';
	exit;
}

$stmt->bind_param('i', $uid);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
	echo 'expired';
	exit;
}

// Check expiry first
if ((int) $row['end_time'] < $now) {
	// Clean up expired code
	$del = $conn->prepare("DELETE FROM `user_login_code` WHERE `user_id` = ?");
	if ($del) { $del->bind_param('i', $uid); $del->execute(); $del->close(); }
	unset($_SESSION['pending_uid']);
	echo 'expired';
	exit;
}

// Constant-time comparison to prevent timing attacks
if (!hash_equals((string) $row['login_code'], $code)) {
	echo 'Code is not correct, try again.';
	exit;
}

// Code is correct — complete login
// Record successful login time
$upd = $conn->prepare("UPDATE `user_login_code` SET `time` = ? WHERE `user_id` = ?");
if ($upd) { $upd->bind_param('ii', $now, $uid); $upd->execute(); $upd->close(); }

// Promote pending session to real authenticated session
unset($_SESSION['pending_uid']);
$_SESSION['unique_id'] = $uid;

// Clean up expired private messages
deleteExpiredPrivateMessages($conn, $uid);

// Update user online status
$stmtS = $conn->prepare("UPDATE `users` SET `status` = 'Active' WHERE `unique_id` = ?");
if ($stmtS) { $stmtS->bind_param('i', $uid); $stmtS->execute(); $stmtS->close(); }

echo 'success';
