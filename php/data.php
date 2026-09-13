<?php
    while($row = mysqli_fetch_assoc($query)){
        $sql2 = "SELECT * FROM messages WHERE (incoming_msg_id = {$row['unique_id']}
                OR outgoing_msg_id = {$row['unique_id']}) AND (outgoing_msg_id = {$outgoing_id} 
                OR incoming_msg_id = {$outgoing_id}) ORDER BY msg_id DESC LIMIT 1";
        $query2 = mysqli_query($conn, $sql2);
        $row2 = mysqli_fetch_assoc($query2);

        $isMsg = (mysqli_num_rows($query2) > 0);
        $rawMsg = $isMsg ? $row2['msg'] : '';

        // check if there are any unread (see=0) messages sent TO the logged-in user FROM this peer
        $hasUnseen = false;
        $sqlUnseen = "SELECT 1 FROM messages WHERE incoming_msg_id = {$outgoing_id} AND outgoing_msg_id = {$row['unique_id']} AND `see` = 0 LIMIT 1";
        $resUnseen = mysqli_query($conn, $sqlUnseen);
        if ($resUnseen && mysqli_num_rows($resUnseen) > 0) {
            $hasUnseen = true;
        }

        // Detect encrypted payload types and build a data-attribute <p> so JS can decrypt
        $msgHtml = '';
        if (!$isMsg) {
            $msgHtml = '<p>No message available</p>';
        } elseif (strpos($rawMsg, 'ENC:DH:') === 0) {
            $json = substr($rawMsg, strlen('ENC:DH:'));
            $obj  = json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($obj['iv'], $obj['ct'])) {
                // peer = the other user in this conversation
                $peer = ($outgoing_id == (int)$row2['outgoing_msg_id']) ? (int)$row['unique_id'] : (int)$row2['outgoing_msg_id'];
                $msgHtml = '<p data-enc="dh" data-peer="' . $peer . '" data-iv="' . htmlspecialchars($obj['iv']) . '" data-ct="' . htmlspecialchars($obj['ct']) . '">[encrypted]</p>';
            } else {
                $msgHtml = '<p>[encrypted]</p>';
            }
        } elseif (strpos($rawMsg, 'ENC:HYBRID:') === 0 || strpos($rawMsg, 'ENC:RSA:') === 0) {
            $msgHtml = '<p>[encrypted]</p>';
        } else {
            $result = $rawMsg;
            (strlen($result) > 28) ? $msg = substr($result, 0, 28) . '...' : $msg = $result;
            $msgHtml = '<p>' . htmlspecialchars($msg) . '</p>';
        }

        if (isset($row2['outgoing_msg_id'])) {
            ($outgoing_id == $row2['outgoing_msg_id']) ? $you = "You: " : $you = "";
        } else {
            $you = "";
        }
        ($row['status'] == "Offline") ? $offline = "offline" : $offline = "online";
        ($outgoing_id == $row['unique_id']) ? $hid_me = "hide" : $hid_me = "";
        $active_chat = "";

        $output .= '<a href="chat/' . $row['unique_id'] . '" class="' . $active_chat . '"' . ($hasUnseen ? ' data-unseen="1"' : '') . '>
                    <div class="content">
                    <img src="data/user/'. $row['img'] .'" alt="">
                    <span>'. htmlspecialchars($row['fname']) . " " . htmlspecialchars($row['lname']) .'</span>
                    <div class="details">
                        ' . ($you ? '<span class="you-prefix">' . htmlspecialchars($you) . '</span>' : '') . $msgHtml . '
                    </div>
                    </div>
                    <div class="status-dot '. $offline .'"><i class="fas fa-circle"></i></div>
                </a>';
    }
?>
