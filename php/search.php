<?php
session_start();
require_once __DIR__ . '/config.php';

$outgoing_id = (int) ($_SESSION['unique_id'] ?? 0);
$searchTerm = $_POST['searchTerm'] ?? '';
$output = '';

if ($outgoing_id > 0 && $searchTerm !== '') {
    $userModel = new User($conn);
    $query = $userModel->search($outgoing_id, $searchTerm);
    if ($query && $query->num_rows > 0) {
        include_once __DIR__ . '/data.php';
    } else {
        $output .= 'Oops! No users were found matching your search';
    }
}

echo $output;
?>
