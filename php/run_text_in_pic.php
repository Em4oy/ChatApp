<?php
/**
 * run_text_in_pic.php
 * Pure-PHP replacement for the former Python text_in_pic server proxy.
 * Accepts the same POST parameters as before so chat.js requires no changes.
 *
 * Expected POST fields:
 *   image_web_path  – web-root-relative path to the image  (used by JS for extract)
 *   image_path      – absolute filesystem path             (alternative, also accepted)
 *   args            – command string, e.g. "extract @IMAGE@" or "embed --mode resize @IMAGE@ @TEXT@"
 *   upload-pic-msg  – text to embed (used when args contains @TEXT@)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/StegoImage.php';

header('Content-Type: application/json; charset=utf-8');

$imageWeb = $_POST['image_web_path'] ?? $_POST['image_path'] ?? '';
$args     = trim($_POST['args'] ?? '');

if ($imageWeb === '') {
    http_response_code(400);
    echo json_encode(['error' => 'image_web_path required']);
    exit;
}

// ------------------------------------------------------------------
// Resolve image path
// ------------------------------------------------------------------
$projectRoot = realpath(__DIR__ . '/..');
$candidate   = preg_replace('/[?#].*$/', '', $imageWeb); // strip query/fragment
if (strpos($candidate, '/') === 0) {
    $candidate = ltrim($candidate, '/');
}

// If the value looks like an absolute path already (from insert-chat.php), use it directly
$abs = null;
if (is_file($imageWeb)) {
    $abs = realpath($imageWeb);
} else {
    $abs = realpath($projectRoot . DIRECTORY_SEPARATOR . $candidate);
}

if ($abs === false || strpos($abs, $projectRoot) !== 0 || !is_file($abs)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid image path', 'path' => $candidate]);
    exit;
}

// ------------------------------------------------------------------
// Determine operation from args string
// ------------------------------------------------------------------
$isEmbed   = (stripos($args, 'embed') !== false);
$isExtract = (stripos($args, 'extract') !== false);

if ($isEmbed) {
    $text = $_POST['upload-pic-msg'] ?? '';
    if ($text === '') {
        // Try to pull text from @TEXT@ substitution inside args itself (legacy path)
        if (preg_match('/@TEXT@\s+(.+)$/i', $args, $m)) {
            $text = trim($m[1]);
        }
    }
    if ($text === '') {
        echo json_encode(['error' => 'no text to embed']);
        exit;
    }
    try {
        StegoImage::embed($abs, $text);
        echo json_encode(['returncode' => 0, 'stdout' => 'OK. Wrote: ' . $abs, 'stderr' => '']);
    } catch (RuntimeException $e) {
        http_response_code(500);
        echo json_encode(['returncode' => 1, 'stdout' => '', 'stderr' => $e->getMessage()]);
    }
} elseif ($isExtract) {
    try {
        $text = StegoImage::extract($abs);
        if ($text === null) {
            echo json_encode(['returncode' => 2, 'stdout' => 'No embedded text found (or unsupported format).', 'stderr' => '']);
        } else {
            echo json_encode(['returncode' => 0, 'stdout' => $text, 'stderr' => '']);
        }
    } catch (RuntimeException $e) {
        http_response_code(500);
        echo json_encode(['returncode' => 1, 'stdout' => '', 'stderr' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'unknown command in args', 'args' => $args]);
}
?>