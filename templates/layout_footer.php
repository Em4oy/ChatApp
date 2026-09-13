<?php
$scripts = $scripts ?? [];
$base = defined('BASE_PATH') ? BASE_PATH : '/';
// Expose BASE_PATH and current user id before any scripts run
echo "<script>window.BASE_PATH='" . addslashes(BASE_PATH) . "';</script>";
$currentUserId = isset($_SESSION['unique_id']) ? (int) $_SESSION['unique_id'] : 0;
echo "<script>window.CURRENT_USER_ID=" . json_encode($currentUserId) . ";</script>";
// Load E2E and keys helpers first so they are available to all page scripts
echo '<script src="' . rtrim($base, '/') . '/assets/javascript/e2e.js"></script>';
echo '<script src="' . rtrim($base, '/') . '/assets/javascript/keys.js"></script>';
// Then load page-specific scripts
foreach ($scripts as $s) {
    if (preg_match('#^(https?:)?//#', $s) || strpos($s, '/') === 0) {
        $src = $s;
    } else {
        $src = rtrim($base, '/') . '/' . ltrim($s, '/');
    }
    echo '<script src="' . htmlspecialchars($src) . '"></script>';
}
?>
</body>
</html>
