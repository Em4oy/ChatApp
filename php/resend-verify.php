<?php
session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
	http_response_code(400);
	echo json_encode(['error' => 'Invalid email']);
	exit;
}

// find user by email
$stmt = $conn->prepare("SELECT unique_id, fname FROM users WHERE email = ? LIMIT 1");
if (!$stmt) { http_response_code(500); echo json_encode(['error' => 'DB error']); exit; }
$stmt->bind_param('s', $email);
$stmt->execute();
$res  = $stmt->get_result();
$user = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$user) {
	// don't reveal whether the email exists
	echo json_encode(['success' => true]);
	exit;
}

$userId = (int) $user['unique_id'];

// only resend if account is still unverified
$stmtC = $conn->prepare("SELECT confirm FROM user_verification WHERE user_id = ? LIMIT 1");
if ($stmtC) {
	$stmtC->bind_param('i', $userId);
	$stmtC->execute();
	$resC = $stmtC->get_result();
	$rowC = $resC ? $resC->fetch_assoc() : null;
	$stmtC->close();
	if ($rowC && (int)$rowC['confirm'] === 1) {
		// already verified — tell JS so it can update the UI
		echo json_encode(['already_verified' => true]);
		exit;
	}
}

// generate a fresh code
$code = bin2hex(random_bytes(32));
$now  = time();

$del = $conn->prepare("DELETE FROM user_verification WHERE user_id = ?");
if ($del) { $del->bind_param('i', $userId); $del->execute(); $del->close(); }

$ins = $conn->prepare("INSERT INTO user_verification (user_id, code, confirm, time) VALUES (?, ?, 0, ?)");
if ($ins) { $ins->bind_param('isi', $userId, $code, $now); $ins->execute(); $ins->close(); }

// build verify URL and send mail
$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$link    = $scheme . '://' . $host . BASE_PATH . 'verify?code=' . urlencode($code);

$to      = $email;
$subject = 'Verify your Chat App account';
$body    = "Hello " . htmlspecialchars($user['fname']) . ",\r\n\r\n"
		 . "You requested a new verification link. Please verify your email address:\r\n\r\n"
		 . $link . "\r\n\r\n"
		 . "This link will expire in 24 hours.\r\n\r\n"
		 . "If you did not request this, please ignore this email.\r\n";
$headers = "From: noreply@" . $host . "\r\n"
		 . "Reply-To: noreply@" . $host . "\r\n"
		 . "X-Mailer: PHP/" . phpversion() . "\r\n"
		 . "MIME-Version: 1.0\r\n"
		 . "Content-Type: text/plain; charset=UTF-8\r\n";

mail($to, $subject, $body, $headers);

echo json_encode(['success' => true]);
