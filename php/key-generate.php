<?php
session_start();
require_once __DIR__ . '/config.php';
if (!isset($_SESSION['unique_id'])) {
    http_response_code(403);
    echo 'Not authenticated';
    exit;
}
$uid = (int) $_SESSION['unique_id'];

$dir = __DIR__ . '/../data/keys/';
if (!is_dir($dir)) mkdir($dir, 0700, true);
$privPath = $dir . $uid . '.priv.pem';
$pubPath = $dir . $uid . '.pub.pem';

// if keys already exist, return success
if (file_exists($privPath) && file_exists($pubPath)) {
    echo 'exists';
    exit;
}

$config = [
    "private_key_type" => OPENSSL_KEYTYPE_RSA,
    "private_key_bits" => 2048,
];

$res = openssl_pkey_new($config);
if (!$res) {
    http_response_code(500);
    echo 'keygen-failed';
    exit;
}
openssl_pkey_export($res, $privPem);
$pubKeyDetails = openssl_pkey_get_details($res);
$pubPem = $pubKeyDetails['key'];

// save with restrictive permissions
file_put_contents($privPath, $privPem);
@chmod($privPath, 0600);
file_put_contents($pubPath, $pubPem);
@chmod($pubPath, 0644);

echo 'ok';

?>