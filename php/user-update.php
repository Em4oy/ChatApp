<?php
session_start();
require_once __DIR__ . '/config.php';
if (!isset($_SESSION['unique_id'])) {
    http_response_code(403);
    echo 'Not authenticated';
    exit;
}

$outgoing_id = (int) $_SESSION['unique_id'];
$userModel = new User($conn);
$me = $userModel->getByUniqueId($outgoing_id);
if (!$me) { http_response_code(404); echo 'User not found'; exit; }

$fname = trim($_POST['fname'] ?? '');
$lname = trim($_POST['lname'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$msgPicPass = trim($_POST['MsgPicPassword'] ?? '');
$msgPicPeriod = intval($_POST['MsgPicPasswordPeriod'] ?? 86400);
$privateChatActivePeriod = intval($_POST['PrivateChatActivePeriod'] ?? 86400);

// basic validation
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo 'Invalid email';
    exit;
}

// check for email uniqueness
$stmt = $conn->prepare("SELECT unique_id FROM users WHERE email = ? AND unique_id != ? LIMIT 1");
$stmt->bind_param('si', $email, $outgoing_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    http_response_code(409);
    echo 'Email already used';
    exit;
}

// handle avatar upload
$imgName = $me['img'];
if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $f = $_FILES['avatar'];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $allowed = ['png','jpg','jpeg'];
    if (!in_array($ext, $allowed)) {
        http_response_code(400); echo 'Invalid avatar format'; exit;
    }
    $destDir = __DIR__ . '/../data/user/';
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);
    $newName = $outgoing_id . '.' . $ext;
    $dest = $destDir . $newName;
    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        http_response_code(500); echo 'Failed to save avatar'; exit;
    }
    $imgName = $newName;
}

// update DB
$fields = [];
$params = '';
$types = '';
$values = [];

$fields[] = 'fname = ?'; $types .= 's'; $values[] = $fname;
$fields[] = 'lname = ?'; $types .= 's'; $values[] = $lname;
$fields[] = 'email = ?'; $types .= 's'; $values[] = $email;
$fields[] = 'img = ?'; $types .= 's'; $values[] = $imgName;
if ($password !== '') {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $fields[] = 'password = ?'; $types .= 's'; $values[] = $hash;
}

// handle MsgPicPassword storage separately
if ($msgPicPass !== '') {
    // hash the msg-in-pic password before storing
    $mpHash = password_hash($msgPicPass, PASSWORD_DEFAULT);
    $now = time();
    $active = max(0, (int)$msgPicPeriod);
    $endTime = $now + $active;
    // upsert into msg_pic_passw table (user_id unique)
    $sqlUp = "INSERT INTO `msg_pic_passw` (`user_id`, `password`, `active`, `end_time`, `time`)"
           . " VALUES (?, ?, ?, ?, ?)"
           . " ON DUPLICATE KEY UPDATE `password` = VALUES(`password`), `active` = VALUES(`active`), `end_time` = VALUES(`end_time`), `time` = VALUES(`time`)";

    // Ensure only one row per user_id. If the table allowed duplicates before,
    // remove any existing rows for this user_id first, then perform the insert.
    $del = $conn->prepare("DELETE FROM `msg_pic_passw` WHERE `user_id` = ?");
    if ($del) {
        $del->bind_param('i', $outgoing_id);
        $del->execute();
        $del->close();
    }

    $up = $conn->prepare($sqlUp);
    if ($up) {
        $up->bind_param('isiii', $outgoing_id, $mpHash, $active, $endTime, $now);
        $up->execute();
        $up->close();
    }
}

$sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE unique_id = ?";
$types .= 'i'; $values[] = $outgoing_id;

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$values);
if ($stmt->execute()) {
    // upsert private_chat_active
    $now = time();
    $delPca = $conn->prepare("DELETE FROM private_chat_active WHERE user_id = ?");
    if ($delPca) { $delPca->bind_param('i', $outgoing_id); $delPca->execute(); $delPca->close(); }
    $insPca = $conn->prepare("INSERT INTO private_chat_active (user_id, Active, time) VALUES (?, ?, ?)");
    if ($insPca) { $insPca->bind_param('iii', $outgoing_id, $privateChatActivePeriod, $now); $insPca->execute(); $insPca->close(); }

    // keep USER_PRIVATE_CHAT_ACTIVE in sync with the new value
    $GLOBALS['USER_PRIVATE_CHAT_ACTIVE']     = $privateChatActivePeriod;
    $_SESSION['USER_PRIVATE_CHAT_ACTIVE']    = $privateChatActivePeriod;

    echo 'success';
} else {
    http_response_code(500);
    echo 'Failed to update';
}

?>