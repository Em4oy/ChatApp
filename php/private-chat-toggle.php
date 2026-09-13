<?php
session_start();
require_once __DIR__ . '/../php/config.php';

if (!isset($_SESSION['unique_id'])) {
	http_response_code(401);
	echo json_encode(['error' => 'Unauthorized']);
	exit;
}

$data       = json_decode(file_get_contents('php://input'), true);
$to_user_id = isset($data['to_user_id']) ? (int) $data['to_user_id'] : 0;
$active     = isset($data['active'])     ? ($data['active'] ? 1 : 0) : 0;

if ($to_user_id <= 0) {
	http_response_code(400);
	echo json_encode(['error' => 'Invalid to_user_id']);
	exit;
}

$user_id = (int) $_SESSION['unique_id'];
$now     = time();

// upsert: delete existing row then insert fresh
$del = $conn->prepare("DELETE FROM private_chat_u2u WHERE user_id = ? AND to_user_id = ?");
if ($del) {
	$del->bind_param('ii', $user_id, $to_user_id);
	$del->execute();
	$del->close();
}

$ins = $conn->prepare("INSERT INTO private_chat_u2u (user_id, to_user_id, Active, time) VALUES (?, ?, ?, ?)");
if ($ins) {
	$ins->bind_param('iiii', $user_id, $to_user_id, $active, $now);
	$ins->execute();
	$ins->close();
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'active' => $active]);
