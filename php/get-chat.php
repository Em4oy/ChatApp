<?php
session_start();
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['unique_id'])) {
    header('Location: ' . BASE_PATH . 'login');
    exit;
}

$outgoing_id = (int) $_SESSION['unique_id'];
$incoming_id = isset($_POST['incoming_id']) ? (int) $_POST['incoming_id'] : 0;
$output = '';

    if ($incoming_id > 0) {
    $chat = new Chat($conn);
    // mark all unread messages from $incoming_id to $outgoing_id as seen
    $chat->markAsSeen($outgoing_id, $incoming_id);
    $result = $chat->getConversation($outgoing_id, $incoming_id);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // format timestamp from DB `time` column using short month name (M)
            $ts = isset($row['time']) ? (int)$row['time'] : 0;
            $timeStr = $ts ? date("j M Y, H:i:s", $ts) : '';
            $isPrivate   = !empty($row['Private']) && (int)$row['Private'] === 1;
            $privateIcon = $isPrivate
                ? '<img src="' . BASE_PATH . 'assets/pics/eye-close-svgrepo-com.svg" alt="private" title="Private message" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;opacity:1;">'
                : '';
            $isImage = preg_match('/\.(png|jpe?g|bmp)$/i', $row['msg']);
            if ((int)$row['outgoing_msg_id'] === $outgoing_id) {
                $output .= '<div class="chat outgoing">'
                    . '<div class="details">';
                if ($isImage) {
                    // outgoing: peer for DH is the recipient (incoming_id)
                    $output .= '<img src="data/chat/' . htmlspecialchars($row['outgoing_msg_id']) . '/' . htmlspecialchars($row['msg']) . '" alt="image" class="chat-img" style="max-width:300px;display:block;border-radius:8px;" data-sender-id="' . htmlspecialchars($row['outgoing_msg_id']) . '" data-peer-id="' . htmlspecialchars($incoming_id) . '">';
                } else {
                    // If message is an encrypted payload, render as data-attributes
                    $msg = $row['msg'];
                    if (strpos($msg, 'ENC:DH:') === 0) {
                        $json = substr($msg, strlen('ENC:DH:'));
                        $obj = json_decode($json, true);
                        if (json_last_error() === JSON_ERROR_NONE && isset($obj['iv'], $obj['ct'])) {
                            // outgoing: peer is the recipient (incoming_id)
                            $output .= '<p data-enc="dh" data-peer="' . htmlspecialchars($incoming_id) . '" data-iv="' . htmlspecialchars($obj['iv']) . '" data-ct="' . htmlspecialchars($obj['ct']) . '">[encrypted message]</p>';
                        } else {
                            $output .= '<p>' . htmlspecialchars($msg) . '</p>';
                        }
                    } elseif (strpos($msg, 'ENC:HYBRID:') === 0) {
                        $json = substr($msg, strlen('ENC:HYBRID:'));
                        $obj = json_decode($json, true);
                        if (json_last_error() === JSON_ERROR_NONE && isset($obj['wk'], $obj['iv'], $obj['ct'])) {
                            $output .= '<p data-enc="hybrid" data-wk="' . htmlspecialchars($obj['wk']) . '" data-iv="' . htmlspecialchars($obj['iv']) . '" data-ct="' . htmlspecialchars($obj['ct']) . '">[encrypted message]</p>';
                        } else {
                            $output .= '<p>' . htmlspecialchars($msg) . '</p>';
                        }
                    } elseif (strpos($msg, 'ENC:RSA:') === 0) {
                        $ct = substr($msg, strlen('ENC:RSA:'));
                        $output .= '<p data-enc="rsa" data-ct="' . htmlspecialchars($ct) . '">[encrypted message]</p>';
                    } else {
                        $output .= '<p>' . htmlspecialchars($msg) . '</p>';
                    }
                }
                $output .= '<span class="time">' . $privateIcon . htmlspecialchars($timeStr) . '</span>'
                    . '</div>'
                    . '</div>';
            } else {
                $output .= '<div class="chat incoming">'
                    . '<img src="data/user/' . htmlspecialchars($row['img']) . '" alt="">'
                    . '<div class="details">';
                if ($isImage) {
                    // incoming: peer for DH is the sender (outgoing_msg_id = A)
                    $output .= '<img src="data/chat/' . htmlspecialchars($row['outgoing_msg_id']) . '/' . htmlspecialchars($row['msg']) . '" alt="image" class="chat-img" style="max-width:300px;display:block;border-radius:8px;" data-sender-id="' . htmlspecialchars($row['outgoing_msg_id']) . '" data-peer-id="' . htmlspecialchars($row['outgoing_msg_id']) . '">';
                } else {
                    $msg = $row['msg'];
                    if (strpos($msg, 'ENC:DH:') === 0) {
                        $json = substr($msg, strlen('ENC:DH:'));
                        $obj = json_decode($json, true);
                        if (json_last_error() === JSON_ERROR_NONE && isset($obj['iv'], $obj['ct'])) {
                            // incoming: peer is the sender
                            $output .= '<p data-enc="dh" data-peer="' . htmlspecialchars($row['outgoing_msg_id']) . '" data-iv="' . htmlspecialchars($obj['iv']) . '" data-ct="' . htmlspecialchars($obj['ct']) . '">[encrypted message]</p>';
                        } else {
                            $output .= '<p>' . htmlspecialchars($msg) . '</p>';
                        }
                    } elseif (strpos($msg, 'ENC:HYBRID:') === 0) {
                        $json = substr($msg, strlen('ENC:HYBRID:'));
                        $obj = json_decode($json, true);
                        if (json_last_error() === JSON_ERROR_NONE && isset($obj['wk'], $obj['iv'], $obj['ct'])) {
                            $output .= '<p data-enc="hybrid" data-wk="' . htmlspecialchars($obj['wk']) . '" data-iv="' . htmlspecialchars($obj['iv']) . '" data-ct="' . htmlspecialchars($obj['ct']) . '">[encrypted message]</p>';
                        } else {
                            $output .= '<p>' . htmlspecialchars($msg) . '</p>';
                        }
                    } elseif (strpos($msg, 'ENC:RSA:') === 0) {
                        $ct = substr($msg, strlen('ENC:RSA:'));
                        $output .= '<p data-enc="rsa" data-ct="' . htmlspecialchars($ct) . '">[encrypted message]</p>';
                    } else {
                        $output .= '<p>' . htmlspecialchars($msg) . '</p>';
                    }
                }
                $output .= '<span class="time">' . $privateIcon . htmlspecialchars($timeStr) . '</span>'
                    . '</div>'
                    . '</div>';
            }
        }
    } else {
        $output .= '<div class="text">No messages</div>';
    }
}

echo $output;

?>