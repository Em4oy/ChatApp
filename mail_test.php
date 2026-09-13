<?php
$success = mail('1@example.com', 'My Subject', "What time is it?");
if (!$success) {
    $errorMessage = error_get_last()['message'];
	echo $errorMessage . " - error";
}
?>