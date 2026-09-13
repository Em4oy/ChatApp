<?php
/**
 * Deletes expired private messages for a given user.
 * Removes all rows from `messages` where:
 *   - incoming_msg_id = $user_id  (the message was sent TO this user)
 *   - Private = 1                 (it was a private message)
 *   - end_time > 0                (expiry was actually set)
 *   - end_time < current unix timestamp (the expiry has passed)
 *
 * Call deleteExpiredPrivateMessages($conn, $user_id) directly,
 * or load this file standalone (GET/POST with a valid session).
 */

function deleteExpiredPrivateMessages(mysqli $conn, int $user_id): void
{
	$now  = time();
	$stmt = $conn->prepare(
		"DELETE FROM `messages`
		 WHERE `incoming_msg_id` = ?
		   AND `Private`         = 1
		   AND `end_time`        > 0
		   AND `end_time`        < ?"
	);
	if ($stmt) {
		$stmt->bind_param('ii', $user_id, $now);
		$stmt->execute();
		$stmt->close();
	}
}

// ── standalone call (direct HTTP request) ───────────────────────────────────
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
	session_start();
	require_once __DIR__ . '/config.php';

	if (!isset($_SESSION['unique_id'])) {
		http_response_code(401);
		echo json_encode(['error' => 'Unauthorized']);
		exit;
	}

	deleteExpiredPrivateMessages($conn, (int) $_SESSION['unique_id']);
	header('Content-Type: application/json');
	echo json_encode(['success' => true]);
}
