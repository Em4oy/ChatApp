<?php
// Basic bootstrap + database connection. Uses a small class-based layer for clarity.
// Keep this file lightweight — it registers an autoloader and exposes $conn for
// legacy procedural code.

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'chatapp');

require_once __DIR__ . '/classes/Autoload.php';

try {
    // Ensure a database connection is available in $conn for existing files.
    $conn = Database::getConnection();
} catch (Throwable $e) {
    // Do not expose internal errors in production; for now echo a generic message.
    echo 'Database connection error';
    exit;
}

// Compute BASE_PATH as the web-accessible path to the project root.
// This is derived from the filesystem path of the project relative to DOCUMENT_ROOT
// so it remains correct even when route files are executed from a subdirectory (e.g. pages/).
$projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
$base = '/';
if ($docRoot && strpos($projectRoot, $docRoot) === 0) {
    $base = substr($projectRoot, strlen($docRoot));
    $base = '/' . trim($base, '/');
    if ($base === '/') {
        $base = '/';
    }
}
define('BASE_PATH', rtrim($base, '/') . '/');

// Set USER_PRIVATE_CHAT_ACTIVE global for the current request.
// Read from DB if a user is logged in; fall back to 43200 (12 hours) otherwise.
$GLOBALS['USER_PRIVATE_CHAT_ACTIVE'] = 43200;
if (isset($_SESSION['unique_id'])) {
    $_uid = (int) $_SESSION['unique_id'];
    $_stmtPca = $conn->prepare("SELECT `Active` FROM `private_chat_active` WHERE `user_id` = ? LIMIT 1");
    if ($_stmtPca) {
        $_stmtPca->bind_param('i', $_uid);
        $_stmtPca->execute();
        $_resPca = $_stmtPca->get_result();
        if ($_resPca && $_pcaRow = $_resPca->fetch_assoc()) {
            $GLOBALS['USER_PRIVATE_CHAT_ACTIVE'] = (int) $_pcaRow['Active'];
        }
        $_stmtPca->close();
    }
    // mirror into session so it survives across requests without re-querying
    $_SESSION['USER_PRIVATE_CHAT_ACTIVE'] = $GLOBALS['USER_PRIVATE_CHAT_ACTIVE'];
}

?>
