<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/StegoImage.php';
if (!isset($_SESSION['unique_id'])) {
    header('Location: ' . BASE_PATH . 'login');
    exit;
}

$outgoing_id = (int) $_SESSION['unique_id'];
$incoming_id = isset($_POST['incoming_id']) ? (int) $_POST['incoming_id'] : 0;
$message = trim($_POST['message'] ?? '');
$plaintextCaption = trim($_POST['plaintext_caption'] ?? $message);

// resolve private-chat flag for this conversation from DB
$privateFlag = 0;
$endTime     = 0;
if ($incoming_id > 0) {
    $stmtPc = $conn->prepare("SELECT Active FROM private_chat_u2u WHERE user_id = ? AND to_user_id = ? LIMIT 1");
    if ($stmtPc) {
        $stmtPc->bind_param('ii', $outgoing_id, $incoming_id);
        $stmtPc->execute();
        $resPc = $stmtPc->get_result();
        if ($resPc && $pcRow = $resPc->fetch_assoc()) {
            $privateFlag = (int) $pcRow['Active'];
        }
        $stmtPc->close();
    }
    // when private chat is on, set end_time = now + USER_PRIVATE_CHAT_ACTIVE period
    if ($privateFlag === 1) {
        $period  = (int) ($GLOBALS['USER_PRIVATE_CHAT_ACTIVE'] ?? 0);
        $endTime = $period > 0 ? time() + $period : 0;
    }
}

$messageConsumedByImage = false;

// Allow sending either text message or an image upload (or both).
$savedAny = false;
if ($incoming_id > 0) {
    $chat = new Chat($conn);

    // handle file upload if present
    if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        // Only allow PNG and JPEG images in chat uploads
        $allowedExt = ['png','jpg','jpeg'];
        $allowedMime = ['image/png','image/jpeg','image/jpg'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime = $file['type'];
        if (in_array($ext, $allowedExt, true) && in_array($mime, $allowedMime, true)) {
            $ts = time();
            $fileName = $incoming_id . '-' . $outgoing_id . '-' . $ts . '.' . $ext;
            $destDir = __DIR__ . '/../data/chat/' . $outgoing_id . '/';
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            $destPath = $destDir . $fileName;
            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                // store the filename in the messages table (as the msg)
                $chat->insertMessage($incoming_id, $outgoing_id, $fileName, $privateFlag, $endTime);
                $savedAny = true;
                // if a caption was provided for this uploaded image, consider it
                // consumed by the image (do not post it as a separate chat message)
                if (!empty($plaintextCaption)) {
                    $messageConsumedByImage = true;
                }
                // If a caption was provided, embed it into the saved image using pure PHP.
                if (!empty($plaintextCaption)) {
                    try {
                        StegoImage::embed($destPath, $plaintextCaption);
                    } catch (RuntimeException $e) {
                        error_log('StegoImage embed failed: ' . $e->getMessage());
                    }
                }
            }
        }
    }

    // handle text message if present (only when it wasn't used as image caption)
    if ($message !== '' && empty($messageConsumedByImage)) {
        $chat->insertMessage($incoming_id, $outgoing_id, $message, $privateFlag, $endTime);
        $savedAny = true;
    }
}

// Optionally return something to the client
if ($savedAny) {
    echo 'success';
} else {
    echo 'error';
}

?>
