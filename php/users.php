<?php
session_start();
require_once __DIR__ . '/config.php';

$outgoing_id = (int) ($_SESSION['unique_id'] ?? 0);
$output = '';
if ($outgoing_id > 0) {
    $userModel = new User($conn);
    $query = $userModel->getAllExcept($outgoing_id);
    if ($query && $query->num_rows === 0) {
        $output .= 'No users are currently online';
    } elseif ($query && $query->num_rows > 0) {
        include_once __DIR__ . '/data.php';
    }
}

echo $output;
?>
