<?php
// Railway Configuration - تنظیمات خودکار برای Railway
// این فایل متغیرهای Environment را بخوانده و تنظیمات را انجام می‌دهد

// =============== Database Configuration ===============
$db_host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost';
$db_port = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 3306;
$db_name = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'railway';
$db_user = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '';

// =============== Admin Configuration ===============
$admin_username = getenv('ADMIN_USERNAME') ?: 'amir';
$admin_display_name = getenv('ADMIN_DISPLAY_NAME') ?: 'مدیر سیستم';
$admin_password = getenv('ADMIN_PASSWORD') ?: 'Aa@123456';
$admin_pinned_username = $admin_username;

// =============== Fonts & UI ===============
$font_text = getenv('FONT_TEXT') ?: 'Kalameh1'; 
$font_heading = getenv('FONT_HEADING') ?: 'Kalameh'; 
$favicon_path = getenv('FAVICON_PATH') ?: 'fav.png';

// =============== Social Links ===============
$social_github_link = 'https://github.com/PouyaFakham/BlackWacker-Secure-Chat';
$social_github_icon = 'icons/github.png';
$social_website_link = 'https://blackwacker.com';
$social_website_icon = 'icons/website.png';
$social_telegram_link = 'https://t.me/PooyaFakham';
$social_telegram_icon = 'icons/telegram.png';

// =============== Features Configuration ===============
$message_lifetime = (int)(getenv('MESSAGE_LIFETIME') ?: 72 * 3600);
$file_size_limit = (int)(getenv('FILE_SIZE_LIMIT') ?: 5 * 1024 * 1024);
$spam_limit_count = (int)(getenv('SPAM_LIMIT_COUNT') ?: 10);
$spam_limit_time = (int)(getenv('SPAM_LIMIT_TIME') ?: 10);
$ban_duration = (int)(getenv('BAN_DURATION') ?: 120);

$enable_intro_popup = (getenv('ENABLE_INTRO_POPUP') !== 'false');

$stickers = ['😎', '👻', '🤖', '🐱', '👤', '🦊', '🦁', '🐸', '🐼', '🐲', '⭐', '🔥', '💀', '👽', '🤡', '👺', '👹', '💖', '🚀', '✅', '❌', '⚠'];

date_default_timezone_set('Asia/Tehran');

if (file_exists('jdf.php')) {
    require_once 'jdf.php';
}

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');
ini_set('memory_limit', '512M');

ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 86400);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'domain' => '', 
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// =============== Encryption Key ===============
$encryption_key_env = getenv('ENCRYPTION_KEY');
if (empty($encryption_key_env)) {
    // تولید کلید جدید اگر موجود نباشد
    $encryption_key_env = bin2hex(random_bytes(32));
    error_log("⚠️ WARNING: ENCRYPTION_KEY not found. Generated new key: " . substr($encryption_key_env, 0, 10) . "...");
}

if (!defined('ENCRYPTION_KEY')) define('ENCRYPTION_KEY', $encryption_key_env);
if (!defined('IV_LENGTH')) define('IV_LENGTH', openssl_cipher_iv_length('aes-256-cbc'));

function encrypt_data($data) {
    $iv = openssl_random_pseudo_bytes(IV_LENGTH);
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', ENCRYPTION_KEY, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function decrypt_data($data) {
    $data = base64_decode($data);
    if (strlen($data) < IV_LENGTH) return false;
    $iv = substr($data, 0, IV_LENGTH);
    $encrypted = substr($data, IV_LENGTH);
    return openssl_decrypt($encrypted, 'aes-256-cbc', ENCRYPTION_KEY, 0, $iv);
}

function get_client_ip() {
    // Railway استفاده از Proxy می‌کند، بنابراین باید این ترتیب را رعایت کنیم
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) return $_SERVER['HTTP_CF_CONNECTING_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

try {
    // ایجاد DSN برای اتصال MySQL
    $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";
    
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) DEFAULT NULL,
        sticker VARCHAR(10) DEFAULT '👤',
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_banned_until INT DEFAULT 0,
        muted_until INT DEFAULT 0,
        is_online TINYINT DEFAULT 0,
        notifications_enabled TINYINT DEFAULT 1,
        user_agent VARCHAR(255) DEFAULT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        last_notif_id INT DEFAULT 0,
        typing_context VARCHAR(100) DEFAULT NULL,
        typing_time INT DEFAULT 0,
        device_token VARCHAR(64) DEFAULT NULL,
        security_code VARCHAR(20) DEFAULT NULL,
        INDEX(last_activity),
        INDEX(is_online),
        INDEX(username),
        INDEX(device_token)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS banned_ips (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL UNIQUE,
        banned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        reason VARCHAR(255) DEFAULT 'Violation'
    )");

    $checkColsUsers = [
        'sticker' => "VARCHAR(10) DEFAULT '👤'",
        'is_banned_until' => "INT DEFAULT 0",
        'muted_until' => "INT DEFAULT 0",
        'is_online' => "TINYINT DEFAULT 0",
        'notifications_enabled' => "TINYINT DEFAULT 1",
        'user_agent' => "VARCHAR(255) DEFAULT NULL",
        'ip_address' => "VARCHAR(45) DEFAULT NULL",
        'password_hash' => "VARCHAR(255) DEFAULT NULL",
        'last_notif_id' => "INT DEFAULT 0",
        'typing_context' => "VARCHAR(100) DEFAULT NULL",
        'typing_time' => "INT DEFAULT 0",
        'device_token' => "VARCHAR(64) DEFAULT NULL",
        'security_code' => "VARCHAR(20) DEFAULT NULL"
    ];
    foreach ($checkColsUsers as $col => $def) {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE '$col'");
        if ($stmt->rowCount() == 0) $pdo->exec("ALTER TABLE users ADD COLUMN $col $def");
    }

    $client_ip = get_client_ip();
    $stmtBan = $pdo->prepare("SELECT id FROM banned_ips WHERE ip_address = ? LIMIT 1");
    $stmtBan->execute([$client_ip]);
    if ($stmtBan->fetch()) {
        ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دسترسی مسدود شد</title>
    <style>
        @font-face { font-family: 'AppBold'; src: url('fonts/<?php echo $font_heading; ?>.ttf') format('truetype'); font-weight: bold; }
        * { box-sizing: border-box; font-family: 'AppBold', sans-serif; }
        body { margin: 0; background: linear-gradient(135deg, #1a0505 0%, #450a0a 100%); color: #fff; display: flex; justify-content: center; align-items: center; min-height: 100vh; overflow: hidden; }
        .ban-container { width: 100%; max-width: 480px; position: relative; z-index: 10; animation: fadeIn 0.6s cubic-bezier(0.22, 1, 0.36, 1); }
        .ban-card { background: rgba(50, 0, 0, 0.6); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); padding: 50px 30px; border-radius: 35px; border: 1px solid rgba(239, 68, 68, 0.3); box-shadow: 0 8px 32px rgba(239, 68, 68, 0.1); text-align: center; position: relative; }
        .ban-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #ef4444, #fca5a5, #ef4444); background-size: 200% 100%; animation: gradientMove 3s ease infinite; border-radius: 35px 35px 0 0; }
        .icon { font-size: 70px; margin-bottom: 25px; display: inline-block; filter: drop-shadow(0 0 25px rgba(239, 68, 68, 0.6)); animation: pulse 2s infinite; }
        .title { font-size: 28px; font-weight: 900; margin-bottom: 15px; color: #ef4444; letter-spacing: -0.5px; text-shadow: 0 5px 15px rgba(239, 68, 68, 0.3); }
        .desc { font-size: 16px; color: #fca5a5; line-height: 1.8; margin-bottom: 35px; padding: 0 10px; opacity: 0.9; }
        .ban-badge { background: rgba(239, 68, 68, 0.15); color: #fca5a5; padding: 10px 20px; border-radius: 50px; font-size: 14px; border: 1px solid rgba(239, 68, 68, 0.3); display: inline-block; margin-bottom: 15px; }
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.95) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.05); } 100% { transform: scale(1); } }
        @keyframes gradientMove { 0% { background-position: 0% 50%; } 100% { background-position: 100% 50%; } }
    </style>
</head>
<body>
    <div class="ban-container">
        <div class="ban-card">
            <div class="icon">🚫</div>
            <div class="title">دسترسی مسدود شد</div>
            <div class="ban-badge">IP: <?php echo $client_ip; ?></div>
            <div class="desc">
                دسترسی دستگاه شما به دلیل نقض قوانین سرور برای همیشه مسدود شده است.
                <br>اگر فکر می‌کنید اشتباهی رخ داده، با پشتیبانی تماس بگیرید.
            </div>
        </div>
    </div>
</body>
</html>
        <?php
        exit;
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS rooms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        type ENUM('public', 'private') DEFAULT 'public',
        created_by INT,
        invite_code VARCHAR(20) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(name),
        INDEX(invite_code)
    )");
    
    $stmt = $pdo->query("SHOW COLUMNS FROM rooms LIKE 'invite_code'");
    if ($stmt->rowCount() == 0) $pdo->exec("ALTER TABLE rooms ADD COLUMN invite_code VARCHAR(20) DEFAULT NULL");
    
    $pdo->exec("INSERT IGNORE INTO rooms (name, type, created_by) VALUES ('گفتگوی عمومی', 'public', 0)");

    $pdo->exec("CREATE TABLE IF NOT EXISTS room_invites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        room_name VARCHAR(50) NOT NULL,
        invited_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(user_id),
        INDEX(room_name)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT DEFAULT NULL,
        room_name VARCHAR(50) DEFAULT NULL,
        reply_to_id INT DEFAULT NULL,
        message LONGTEXT,
        file_path VARCHAR(255) DEFAULT NULL,
        file_name VARCHAR(255) DEFAULT NULL,
        file_token VARCHAR(64) DEFAULT NULL,
        msg_type ENUM('text', 'file', 'voice', 'image', 'video') DEFAULT 'text',
        is_edited TINYINT DEFAULT 0,
        is_read TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(room_name),
        INDEX(sender_id),
        INDEX(receiver_id),
        INDEX(file_token),
        INDEX(created_at),
        INDEX(is_read)
    )");
    
    $checkColsMsgs = [
        'is_read' => "TINYINT DEFAULT 0",
        'msg_type' => "ENUM('text', 'file', 'voice', 'image', 'video') DEFAULT 'text'"
    ];
    foreach ($checkColsMsgs as $col => $def) {
        $stmt = $pdo->query("SHOW COLUMNS FROM messages LIKE '$col'");
        if ($stmt->rowCount() == 0) $pdo->exec("ALTER TABLE messages ADD COLUMN $col $def");
        else if ($col === 'msg_type') $pdo->exec("ALTER TABLE messages MODIFY COLUMN $col $def");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) NOT NULL UNIQUE,
        setting_value VARCHAR(255) DEFAULT NULL
    )");
    
    $stmtSet = $pdo->prepare("SELECT id FROM system_settings WHERE setting_key = ?");
    $stmtSet->execute(['lock_upload']);
    if (!$stmtSet->fetch()) {
        $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)")->execute(['lock_upload', '0']);
    }

    $stmtSetVoice = $pdo->prepare("SELECT id FROM system_settings WHERE setting_key = ?");
    $stmtSetVoice->execute(['lock_voice']);
    if (!$stmtSetVoice->fetch()) {
        $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)")->execute(['lock_voice', '0']);
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS user_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reporter_id INT NOT NULL,
        reported_id INT NOT NULL,
        reason TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(created_at)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        name VARCHAR(50) PRIMARY KEY,
        value VARCHAR(255) DEFAULT NULL
    )");

    $stmtS = $pdo->prepare("SELECT name FROM settings WHERE name = ?");
    $stmtS->execute(['site_lock']);
    if (!$stmtS->fetch()) {
        $pdo->prepare("INSERT INTO settings (name, value) VALUES (?, ?)")->execute(['site_lock', '0']);
    }
    
    $stmtR = $pdo->prepare("SELECT name FROM settings WHERE name = ?");
    $stmtR->execute(['report_lock']);
    if (!$stmtR->fetch()) {
        $pdo->prepare("INSERT INTO settings (name, value) VALUES (?, ?)")->execute(['report_lock', '0']);
    }

    $stmtAR = $pdo->prepare("SELECT name FROM settings WHERE name = ?");
    $stmtAR->execute(['auto_reset_enabled']);
    if (!$stmtAR->fetch()) {
        $pdo->prepare("INSERT INTO settings (name, value) VALUES (?, ?)")->execute(['auto_reset_enabled', '0']);
    }
    $stmtAR->execute(['auto_reset_target']);
    if (!$stmtAR->fetch()) {
        $pdo->prepare("INSERT INTO settings (name, value) VALUES (?, ?)")->execute(['auto_reset_target', '0']);
    }
    $stmtAR->execute(['auto_reset_recurring']);
    if (!$stmtAR->fetch()) {
        $pdo->prepare("INSERT INTO settings (name, value) VALUES (?, ?)")->execute(['auto_reset_recurring', '0']);
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS global_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
} catch (PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    die("خطا در سیستم پایگاه داده. لطفا بعدا تلاش کنید.");
}

function perform_system_reset($pdo) {
    global $admin_username;
    
    $files = glob('uploads/*');
    foreach($files as $file){ 
        if(is_file($file) && basename($file)!=='index.php' && basename($file)!=='.htaccess') {
            @unlink($file); 
        }
    }
    
    $pdo->exec("DELETE FROM rooms WHERE name != 'گفتگوی عمومی'");
    $pdo->exec("TRUNCATE TABLE messages");
    $pdo->exec("TRUNCATE TABLE room_invites");
    $pdo->exec("TRUNCATE TABLE banned_ips");
    $pdo->exec("TRUNCATE TABLE user_reports");
    $pdo->exec("TRUNCATE TABLE global_alerts");
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$admin_username]);
    $adminId = $stmt->fetchColumn();
    
    if ($adminId) {
        $pdo->prepare("UPDATE rooms SET created_by = ?")->execute([$adminId]);
        $pdo->prepare("DELETE FROM users WHERE id != ?")->execute([$adminId]);
        $pdo->prepare("UPDATE users SET last_notif_id = 0, is_banned_until = 0, muted_until = 0, typing_time = 0, device_token = NULL WHERE id = ?")->execute([$adminId]);
    }
}

function clean_old_data($pdo, $lifetime) {
    $limit = date('Y-m-d H:i:s', time() - $lifetime);
    
    $stmt = $pdo->prepare("SELECT file_path FROM messages WHERE created_at < ? AND file_path IS NOT NULL");
    $stmt->execute([$limit]);
    while ($row = $stmt->fetch()) {
        if (file_exists($row['file_path'])) @unlink($row['file_path']);
    }
    
    $pdo->prepare("DELETE FROM messages WHERE created_at < ?")->execute([$limit]);
    $pdo->prepare("DELETE FROM room_invites WHERE created_at < DATE_SUB(NOW(), INTERVAL 3 DAY)")->execute();
    
    $pdo->prepare("UPDATE users SET is_online = 0 WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE)")->execute();
}

try {
    $stmtChk = $pdo->prepare("SELECT value FROM settings WHERE name = 'auto_reset_enabled'");
    $stmtChk->execute();
    if ($stmtChk->fetchColumn() === '1') {
        $stmtTgt = $pdo->prepare("SELECT value FROM settings WHERE name = 'auto_reset_target'");
        $stmtTgt->execute();
        $target = (int)$stmtTgt->fetchColumn();
        
        if (time() >= $target && $target > 0) {
            perform_system_reset($pdo);
            
            $stmtRec = $pdo->prepare("SELECT value FROM settings WHERE name = 'auto_reset_recurring'");
            $stmtRec->execute();
            $isRecurring = $stmtRec->fetchColumn() === '1';
            
            if ($isRecurring) {
                $newTarget = time() + (24 * 3600);
                $pdo->prepare("UPDATE settings SET value = ? WHERE name = 'auto_reset_target'")->execute([$newTarget]);
            } else {
                $pdo->prepare("UPDATE settings SET value = '0' WHERE name = 'auto_reset_enabled'")->execute();
                $pdo->prepare("UPDATE settings SET value = '0' WHERE name = 'auto_reset_target'")->execute();
            }
        }
    }
} catch (Exception $e) {}

if (!is_dir('uploads')) {
    mkdir('uploads', 0755, true);
    file_put_contents('uploads/index.php', '<?php header("HTTP/1.0 403 Forbidden");');
    file_put_contents('uploads/.htaccess', 'Deny from all');
}

if (rand(1, 50) === 1) {
    clean_old_data($pdo, $message_lifetime);
}

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
if (empty($_SESSION['bw_nonce'])) $_SESSION['bw_nonce'] = bin2hex(random_bytes(16));
?>
