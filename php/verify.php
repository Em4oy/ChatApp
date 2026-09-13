<?php
session_start();
require_once __DIR__ . '/config.php';

$code   = trim($_GET['code'] ?? '');
$status = 'error'; // 'ok' | 'error' | 'expired' | 'already'

if ($code !== '') {
	$now  = time();
	$ttl  = 86400; // 24 hours

	$stmt = $conn->prepare(
		"SELECT `user_id`, `confirm`, `time` FROM `user_verification` WHERE `code` = ? LIMIT 1"
	);
	if ($stmt) {
		$stmt->bind_param('s', $code);
		$stmt->execute();
		$res = $stmt->get_result();
		$row = $res ? $res->fetch_assoc() : null;
		$stmt->close();

		if ($row === null) {
			$status = 'error'; // code not found
		} elseif ((int)$row['confirm'] === 1) {
			$status = 'already'; // already verified
		} elseif (($now - (int)$row['time']) > $ttl) {
			$status = 'expired'; // link too old
		} else {
			// mark as confirmed
			$upd = $conn->prepare(
				"UPDATE `user_verification` SET `confirm` = 1 WHERE `user_id` = ?"
			);
			if ($upd) {
				$uid = (int) $row['user_id'];
				$upd->bind_param('i', $uid);
				$upd->execute();
				$upd->close();
			}
			// log the user in
			$_SESSION['unique_id'] = (int) $row['user_id'];
			$status = 'ok';
		}
	}
}

// redirect to verify page with status parameter
header('Location: ' . BASE_PATH . 'verify?status=' . $status);
exit;
