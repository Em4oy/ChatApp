<?php
session_start();
require_once __DIR__ . '/config.php';

$auth     = new Auth($conn);
$email    = $_POST['email']    ?? '';
$password = $_POST['password'] ?? '';

$res = $auth->login($email, $password);
if ($res['success']) {
    $uid = (int) $res['user']['unique_id'];

    // Generate a 6-digit login code
    $code    = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $now     = time();
    $endTime = $now + 900; // valid for 15 minutes

    // Upsert into user_login_code
    $del = $conn->prepare("DELETE FROM `user_login_code` WHERE `user_id` = ?");
    if ($del) { $del->bind_param('i', $uid); $del->execute(); $del->close(); }

    $ins = $conn->prepare(
        "INSERT INTO `user_login_code` (`user_id`, `login_code`, `end_time`, `time`) VALUES (?, ?, ?, 0)"
    );
    if ($ins) {
        $ins->bind_param('iss', $uid, $code, $endTime);
        $ins->execute();
        $ins->close();
    }

    // Store pending user in session (not yet authenticated)
    $_SESSION['pending_uid'] = $uid;

    // Send the code by email
    $userEmail = $res['user']['email'];
    $fname     = $res['user']['fname'] ?? '';
    $scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $subject   = 'Your Chat App login code';
    $body      = "Hello " . htmlspecialchars($fname) . ",\r\n\r\n"
               . "Your login verification code is:\r\n\r\n"
               . "  " . $code . "\r\n\r\n"
               . "This code is valid for 15 minutes.\r\n\r\n"
               . "If you did not attempt to log in, please ignore this email.\r\n";
    $headers   = "From: noreply@" . $host . "\r\n"
               . "Reply-To: noreply@" . $host . "\r\n"
               . "MIME-Version: 1.0\r\n"
               . "Content-Type: text/plain; charset=UTF-8\r\n";
    mail($userEmail, $subject, $body, $headers);

    echo '2fa';
} else {
    echo $res['message'];
}
?>
