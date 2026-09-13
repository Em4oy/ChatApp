<?php
session_start();
require_once __DIR__ . '/../php/config.php';
if (!isset($_SESSION['unique_id'])) {
    header('Location: ' . BASE_PATH . 'login');
    exit;
}

$user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

$userModel = new User($conn);
// current logged-in user
$me = $userModel->getByUniqueId((int) $_SESSION['unique_id']);

$row = null;
if ($user_id > 0) {
    $row = $userModel->getByUniqueId($user_id);
}

// load private chat toggle state for this conversation
$privateChatActive = 0;
if ($user_id > 0 && isset($_SESSION['unique_id'])) {
    $myId = (int) $_SESSION['unique_id'];
    $stmtPc = $conn->prepare("SELECT Active FROM private_chat_u2u WHERE user_id = ? AND to_user_id = ? LIMIT 1");
    if ($stmtPc) {
        $stmtPc->bind_param('ii', $myId, $user_id);
        $stmtPc->execute();
        $resPc = $stmtPc->get_result();
        if ($resPc && $pcRow = $resPc->fetch_assoc()) {
            $privateChatActive = (int) $pcRow['Active'];
        }
        $stmtPc->close();
    }
}

View::display('chat', ['row' => $row, 'user_id' => $user_id, 'me' => $me, 'privateChatActive' => $privateChatActive]);

?>