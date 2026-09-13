<?php
class Auth
{
    private $conn;
    private $userModel;
    private $uploader;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->userModel = new User($conn);
        $this->uploader = new FileUploader();
    }

    public function register(array $input, array $file = null)
    {
        // basic validation
        if (empty($input['fname']) || empty($input['lname']) || empty($input['email']) || empty($input['password'])) {
            return ['success' => false, 'message' => 'All input fields are required!'];
        }
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email is not valid'];
        }

        // check existing
        if ($this->userModel->findByEmail($input['email'])) {
            return ['success' => false, 'message' => 'This email already exists'];
        }

        // handle file
        if ($file && isset($file['image'])) {
            try {
                $imageName = $this->uploader->handle($file['image']);
            } catch (Throwable $e) {
                return ['success' => false, 'message' => $e->getMessage()];
            }
        } else {
            return ['success' => false, 'message' => 'Image is required'];
        }

        $unique_id = rand(time(), 100000000);
        $status = 'Active';
        // use password_hash for better security
        $passwordHash = password_hash($input['password'], PASSWORD_DEFAULT);

        $data = [
            'unique_id' => $unique_id,
            'fname' => $input['fname'],
            'lname' => $input['lname'],
            'email' => $input['email'],
            'password' => $passwordHash,
            'img' => $imageName,
            'status' => $status
        ];

        $created = $this->userModel->create($data);
        if ($created) {
            $user = $this->userModel->findByEmail($input['email']);
            // insert verification record and send confirmation email
            $this->sendVerificationEmail($user, $input['email']);
            return ['success' => true, 'user' => $user];
        }
        return ['success' => false, 'message' => 'Something went wrong. Please try again!'];
    }

    public function login(string $email, string $password)
    {
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'All input fields are required!'];
        }
        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            return ['success' => false, 'message' => 'This email does not exist'];
        }

        // support both old md5 and new password_hash
        $stored = $user['password'];
        $valid = false;
        if (password_verify($password, $stored)) {
            $valid = true;
        } elseif (md5($password) === $stored) {
            $valid = true;
        }

        if (!$valid) {
            return ['success' => false, 'message' => 'Email or Password is Incorrect!'];
        }

        // check email verification
        $stmtV = $this->conn->prepare(
            "SELECT `confirm` FROM `user_verification` WHERE `user_id` = ? LIMIT 1"
        );
        if ($stmtV) {
            $uid = (int) $user['unique_id'];
            $stmtV->bind_param('i', $uid);
            $stmtV->execute();
            $resV = $stmtV->get_result();
            $rowV = $resV ? $resV->fetch_assoc() : null;
            $stmtV->close();
            // if a verification row exists and confirm = 0, block login
            if ($rowV !== null && (int)$rowV['confirm'] === 0) {
                return ['success' => false, 'message' => 'Please verify your email address before logging in.'];
            }
        }

        $this->userModel->updateStatus((int)$user['unique_id'], 'Active');
        return ['success' => true, 'user' => $user];
    }

    /**
     * Generate a verification code, store it in user_verification, and email it.
     */
    private function sendVerificationEmail(array $user, string $email): void
    {
        $userId = (int) $user['unique_id'];
        $code   = bin2hex(random_bytes(32)); // 64-char hex token
        $now    = time();

        // upsert: remove any old code for this user, insert fresh
        $del = $this->conn->prepare("DELETE FROM `user_verification` WHERE `user_id` = ?");
        if ($del) {
            $del->bind_param('i', $userId);
            $del->execute();
            $del->close();
        }

        $ins = $this->conn->prepare(
            "INSERT INTO `user_verification` (`user_id`, `code`, `confirm`, `time`) VALUES (?, ?, 0, ?)"
        );
        if ($ins) {
            $ins->bind_param('isi', $userId, $code, $now);
            $ins->execute();
            $ins->close();
        }

        // build verify URL
        $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $link    = $scheme . '://' . $host . BASE_PATH . 'verify?code=' . urlencode($code);

        $to      = $email;
        $subject = 'Verify your Chat App account';
        $body    = "Hello " . htmlspecialchars($user['fname']) . ",\r\n\r\n"
                 . "Thank you for signing up. Please verify your email address by clicking the link below:\r\n\r\n"
                 . $link . "\r\n\r\n"
                 . "This link will expire in 24 hours.\r\n\r\n"
                 . "If you did not create an account, please ignore this email.\r\n";
        $headers = "From: noreply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n"
                 . "Reply-To: noreply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n"
                 . "X-Mailer: PHP/" . phpversion() . "\r\n"
                 . "MIME-Version: 1.0\r\n"
                 . "Content-Type: text/plain; charset=UTF-8\r\n";

        mail($to, $subject, $body, $headers);
    }
}

?>