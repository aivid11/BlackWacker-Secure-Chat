<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['token'])) {
    header("HTTP/1.0 403 Forbidden");
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT is_banned_until FROM users WHERE id = ?");
$stmt->execute([$userId]);
if ($stmt->fetchColumn() > time()) {
    header("HTTP/1.0 403 Forbidden");
    exit;
}

$token = $_GET['token'];
if (strlen($token) !== 32) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

$stmt = $pdo->prepare("SELECT file_path, file_name, msg_type FROM messages WHERE file_token = ?");
$stmt->execute([$token]);
$file = $stmt->fetch();

if ($file && file_exists($file['file_path'])) {
    $content = file_get_contents($file['file_path']);
    $decrypted = decrypt_data($content);
    
    if ($decrypted === false) {
         header("HTTP/1.0 500 Internal Server Error");
         die("Decryption Error");
    }

    $ext = strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION)); 
    
    $mimeTypes = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png', 'gif' => 'image/gif',
        'webp' => 'image/webp',
        'mp3' => 'audio/mpeg', 'wav' => 'audio/wav', 'ogg' => 'audio/ogg', 'webm' => 'audio/webm',
        'mp4' => 'video/mp4', 'm4a' => 'audio/mp4'
    ];

    $isMedia = isset($mimeTypes[$ext]);
    $dl = isset($_GET['dl']);
    
    $accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
    $isBrowserNav = (strpos($accept, 'text/html') !== false);

    $randomName = bin2hex(random_bytes(16));
    $finalName = $randomName . ($ext ? '.' . $ext : '');

    if ($isMedia && !$dl && !$isBrowserNav) {
        header("Content-Type: " . $mimeTypes[$ext]);
        header("Content-Disposition: inline; filename=\"".$finalName."\"");
    } else {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.$finalName.'"');
    }

    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header("Content-Length: " . strlen($decrypted));
    
    echo $decrypted;
    exit;
} else {
    header("HTTP/1.0 404 Not Found");
}
?>