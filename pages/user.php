<?php
session_start();
require_once __DIR__ . '/../php/config.php';
if (!isset($_SESSION['unique_id'])) {
    header('Location: ' . BASE_PATH . 'login');
    exit;
}

$userModel = new User($conn);
$me = $userModel->getByUniqueId((int) $_SESSION['unique_id']);
if (!$me) {
    header('Location: ' . BASE_PATH . 'login');
    exit;
}

// fetch msg_pic_passw row for current user if exists
$msgPic = null;
$stmt = $conn->prepare("SELECT password, active, end_time, time FROM msg_pic_passw WHERE user_id = ? LIMIT 1");
if ($stmt) {
    $uid = (int) $_SESSION['unique_id'];
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $row = $res->fetch_assoc()) {
        $msgPic = $row;
    }
}

// fetch private_chat_active row for current user if exists
$privateChatActive = null;
$stmt2 = $conn->prepare("SELECT Active, time FROM private_chat_active WHERE user_id = ? LIMIT 1");
if ($stmt2) {
    $uid = (int) $_SESSION['unique_id'];
    $stmt2->bind_param('i', $uid);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    if ($res2 && $row2 = $res2->fetch_assoc()) {
        $privateChatActive = $row2;
    }
}

View::display('user', ['me' => $me, 'msgPic' => $msgPic, 'privateChatActive' => $privateChatActive]);

?>