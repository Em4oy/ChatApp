<?php
class User
{
    private $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function findByEmail(string $email)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function create(array $data)
    {
        $stmt = $this->conn->prepare("INSERT INTO users (unique_id, fname, lname, email, password, img, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('issssss', $data['unique_id'], $data['fname'], $data['lname'], $data['email'], $data['password'], $data['img'], $data['status']);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function updateStatus(int $unique_id, string $status)
    {
        $stmt = $this->conn->prepare("UPDATE users SET status = ? WHERE unique_id = ?");
        $stmt->bind_param('si', $status, $unique_id);
        return $stmt->execute();
    }

    public function getByUniqueId(int $unique_id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE unique_id = ? LIMIT 1");
        $stmt->bind_param('i', $unique_id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc();
    }

    public function search(int $outgoing_id, string $term)
    {
        $like = "%" . $term . "%";
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE NOT unique_id = ? AND (fname LIKE ? OR lname LIKE ?) ");
        $stmt->bind_param('iss', $outgoing_id, $like, $like);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getAllExcept(int $outgoing_id)
    {
        // Order by the most recent message exchanged with the logged-in user (DESC),
        // users with no conversation fall back to user_id DESC (original order).
        $stmt = $this->conn->prepare(
            "SELECT u.*,
                    MAX(m.time) AS last_msg_time
             FROM users u
             LEFT JOIN messages m
               ON (m.outgoing_msg_id = u.unique_id AND m.incoming_msg_id = ?)
               OR (m.incoming_msg_id = u.unique_id AND m.outgoing_msg_id = ?)
             WHERE u.unique_id != ?
             GROUP BY u.unique_id
             ORDER BY last_msg_time DESC, u.user_id DESC"
        );
        $stmt->bind_param('iii', $outgoing_id, $outgoing_id, $outgoing_id);
        $stmt->execute();
        return $stmt->get_result();
    }
}

?>