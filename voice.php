<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['token'])) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT is_banned_until FROM users WHERE id = ?");
$stmt->execute([$userId]);
if ($stmt->fetchColumn() > time()) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

$token = $_GET['token'];
if (strlen($token) !== 32) {
    header("HTTP/1.1 404 Not Found");
    exit;
}

$stmt = $pdo->prepare("SELECT file_path, msg_type FROM messages WHERE file_token = ? AND msg_type = 'voice'");
$stmt->execute([$token]);
$file = $stmt->fetch();

if (!$file || !file_exists($file['file_path'])) {
    header("HTTP/1.1 404 Not Found");
    exit;
}

$content = file_get_contents($file['file_path']);
$decrypted = decrypt_data($content);

if ($decrypted === false) {
    header("HTTP/1.1 500 Internal Server Error");
    exit;
}

$fileSize = strlen($decrypted);
$start = 0;
$end = $fileSize - 1;

if (isset($_SERVER['HTTP_RANGE'])) {
    $c_start = $start;
    $c_end = $end;

    list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
    
    if (strpos($range, ',') !== false) {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header("Content-Range: bytes */$fileSize");
        exit;
    }

    if ($range == '-') {
        $c_start = $fileSize - substr($range, 1);
    } else {
        $rangeParts = explode('-', $range);
        $c_start = $rangeParts[0];
        $c_end = (isset($rangeParts[1]) && is_numeric($rangeParts[1])) ? $rangeParts[1] : $c_end;
    }

    $c_end = ($c_end > $end) ? $end : $c_end;

    if ($c_start > $c_end || $c_start > $fileSize - 1 || $c_end >= $fileSize) {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header("Content-Range: bytes */$fileSize");
        exit;
    }

    $start = $c_start;
    $end = $c_end;
    
    header('HTTP/1.1 206 Partial Content');
    header("Content-Range: bytes $start-$end/$fileSize");
} else {
    header('HTTP/1.1 200 OK');
}

$length = $end - $start + 1;

while (ob_get_level()) {
    ob_end_clean();
}

header("Content-Type: audio/webm");
header("Accept-Ranges: bytes");
header("Content-Length: " . $length);
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$bufferSize = 8192;
$pos = $start;

while ($pos <= $end) {
    $bytesToRead = min($bufferSize, $end - $pos + 1);
    echo substr($decrypted, $pos, $bytesToRead);
    flush();
    $pos += $bytesToRead;
    
    if (connection_status() != 0) {
        break;
    }
}
exit;