<?php
class Chat
{
    private $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function insertMessage(int $incoming_id, int $outgoing_id, string $message, int $private = 0, int $endTime = 0): bool
    {
        // Record the message with a unix timestamp in the `time` column; see = 0 (unread)
        $ts  = time();
        $see = 0;
        $stmt = $this->conn->prepare("INSERT INTO messages (incoming_msg_id, outgoing_msg_id, msg, `time`, `Private`, `end_time`, `see`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('iisiiii', $incoming_id, $outgoing_id, $message, $ts, $private, $endTime, $see);
        return $stmt->execute();
    }

    /**
     * Mark all unread messages sent TO $viewer_id FROM $sender_id as seen (see = 1).
     * Called when the recipient opens the conversation.
     */
    public function markAsSeen(int $viewer_id, int $sender_id): void
    {
        $stmt = $this->conn->prepare(
            "UPDATE messages SET `see` = 1
             WHERE incoming_msg_id = ? AND outgoing_msg_id = ? AND `see` = 0"
        );
        if ($stmt) {
            $stmt->bind_param('ii', $viewer_id, $sender_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    public function getConversation(int $outgoing_id, int $incoming_id)
    {
        // Order by the message timestamp to ensure chronological order.
        // Exclude expired private messages: Private = 1, end_time > 0, end_time < now.
        $now = time();
        $sql = "SELECT * FROM messages LEFT JOIN users ON users.unique_id = messages.outgoing_msg_id
                WHERE ((outgoing_msg_id = ? AND incoming_msg_id = ?) OR (outgoing_msg_id = ? AND incoming_msg_id = ?))
                  AND NOT (`Private` = 1 AND `end_time` > 0 AND `end_time` < ?)
                ORDER BY `time`";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('iiiii', $outgoing_id, $incoming_id, $incoming_id, $outgoing_id, $now);
        $stmt->execute();
        return $stmt->get_result();
    }
}

?>