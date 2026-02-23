<?php
require_once 'config.php';
header('Content-Type: application/json');

if (isset($_GET['logout'])) {
    if (!isset($_GET['nonce']) || !isset($_SESSION['logout_nonce']) || $_GET['nonce'] !== $_SESSION['logout_nonce']) {
        echo json_encode(['status' => 'error', 'message' => 'درخواست نامعتبر']);
        exit;
    }

    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("UPDATE users SET last_activity = NOW(), is_online = 0 WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    }
    session_unset();
    session_destroy();
    setcookie('admin_token', '', time() - 3600, '/');
    setcookie('device_token', '', time() - 3600, '/'); 
    
    echo json_encode(['status' => 'success']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['status' => 'error', 'message' => 'نشست منقضی شده است']);
    exit;
}

if (isset($_POST['action'])) {
    if ($_POST['action'] === 'check_username_availability') {
        $u = trim(preg_replace('/\s+/', ' ', $_POST['username']));
        $u = htmlspecialchars(strip_tags($u), ENT_QUOTES, 'UTF-8');
        if (empty($u)) {
            echo json_encode(['status' => 'empty']);
            exit;
        }
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$u]);
        if ($stmt->fetch()) {
            echo json_encode(['status' => 'taken', 'message' => 'این نام کاربری قبلاً ثبت شده است']);
        } else {
            echo json_encode(['status' => 'available']);
        }
        exit;
    }

    if ($_POST['action'] === 'report_user') {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'عدم دسترسی']);
            exit;
        }

        $stmtLock = $pdo->prepare("SELECT value FROM settings WHERE name = 'report_lock'");
        $stmtLock->execute();
        if ($stmtLock->fetchColumn() === '1') {
            echo json_encode(['status' => 'error', 'message' => 'سیستم گزارش‌دهی موقتاً غیرفعال شده است']);
            exit;
        }

        if (!isset($_POST['captcha']) || !isset($_SESSION['report_captcha_result']) || $_POST['captcha'] != $_SESSION['report_captcha_result']) {
            echo json_encode(['status' => 'error', 'message' => 'کد امنیتی اشتباه است']);
            exit;
        }

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM user_reports WHERE reporter_id = ?");
        $stmtCount->execute([$_SESSION['user_id']]);
        if ($stmtCount->fetchColumn() >= 3) {
            echo json_encode(['status' => 'error', 'message' => 'شما به سقف ارسال گزارش (۳ عدد) رسیده‌اید']);
            exit;
        }

        $targetUsername = trim(preg_replace('/\s+/', ' ', $_POST['target_username']));
        $targetUsername = htmlspecialchars(strip_tags($targetUsername), ENT_QUOTES, 'UTF-8');
        
        $reason = trim($_POST['reason']);
        $reason = htmlspecialchars(strip_tags($reason), ENT_QUOTES, 'UTF-8');

        if (empty($targetUsername) || empty($reason)) {
            echo json_encode(['status' => 'error', 'message' => 'لطفاً نام کاربر و دلیل گزارش را وارد کنید']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$targetUsername]);
            $targetId = $stmt->fetchColumn();

            if (!$targetId) {
                echo json_encode(['status' => 'error', 'message' => 'کاربر مورد نظر یافت نشد']);
                exit;
            }

            if ($targetId == $_SESSION['user_id']) {
                echo json_encode(['status' => 'error', 'message' => 'شما نمی‌توانید خودتان را گزارش کنید']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO user_reports (reporter_id, reported_id, reason, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $targetId, $reason]);

            echo json_encode(['status' => 'success', 'message' => 'گزارش شما با موفقیت ثبت شد']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'خطا در ثبت گزارش']);
        }
        exit;
    }

    if ($_POST['action'] === 'invite_user') {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'عدم دسترسی']);
            exit;
        }
        
        $targetUser = trim(preg_replace('/\s+/', ' ', $_POST['username']));
        $targetUser = htmlspecialchars(strip_tags($targetUser), ENT_QUOTES, 'UTF-8');
        
        $roomName = trim($_POST['room_name']);
        $roomName = htmlspecialchars(strip_tags($roomName), ENT_QUOTES, 'UTF-8');
        
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$targetUser]);
            $uid = $stmt->fetchColumn();
            
            if (!$uid) {
                echo json_encode(['status' => 'error', 'message' => 'کاربر یافت نشد']);
                exit;
            }
            
            $chk = $pdo->prepare("SELECT id FROM room_invites WHERE user_id = ? AND room_name = ?");
            $chk->execute([$uid, $roomName]);
            if ($chk->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'کاربر قبلاً دعوت شده است']);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO room_invites (user_id, room_name, invited_by) VALUES (?, ?, ?)");
            $stmt->execute([$uid, $roomName, $_SESSION['user_id']]);
            
            echo json_encode(['status' => 'success', 'message' => "دعوت‌نامه با موفقیت برای کاربر ارسال شد"]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'خطا در دیتابیس']);
        }
        exit;
    }

    if ($_POST['action'] === 'refresh_captcha') {
        $n1 = rand(10, 50);
        $n2 = rand(1, 9);
        $_SESSION['captcha_result'] = $n1 + $n2;
        echo json_encode(['status' => 'success', 'n1' => $n1, 'n2' => $n2]);
        exit;
    }

    if ($_POST['action'] === 'refresh_report_captcha') {
        $n1 = rand(1, 9);
        $n2 = rand(1, 9);
        $_SESSION['report_captcha_result'] = $n1 + $n2;
        echo json_encode(['status' => 'success', 'n1' => $n1, 'n2' => $n2]);
        exit;
    }

    if ($_POST['action'] === 'login_check') {
        $u = trim(preg_replace('/\s+/', ' ', $_POST['username']));
        $u = htmlspecialchars(strip_tags($u), ENT_QUOTES, 'UTF-8');
        if ($u === $admin_username) {
            echo json_encode(['status' => 'password_required']);
        } else {
            echo json_encode(['status' => 'proceed']);
        }
        exit;
    }
    
    if ($_POST['action'] === 'login_with_code') {
        if (!isset($_POST['captcha']) || $_POST['captcha'] != $_SESSION['captcha_result']) {
            echo json_encode(['status' => 'error', 'message' => 'کد امنیتی اشتباه است']);
            exit;
        }
        
        $u = trim(preg_replace('/\s+/', ' ', $_POST['username']));
        $u = htmlspecialchars(strip_tags($u), ENT_QUOTES, 'UTF-8');
        
        $secCode = trim($_POST['security_code']);
        $secCode = htmlspecialchars(strip_tags($secCode), ENT_QUOTES, 'UTF-8');
        
        $ip = get_client_ip();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$u]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'کاربر یافت نشد']);
            exit;
        }
        
        if ($user['security_code'] !== $secCode) {
            echo json_encode(['status' => 'error', 'message' => 'کد امنیتی وارد شده اشتباه است']);
            exit;
        }
        
        if ($user['is_banned_until'] > time()) {
            echo json_encode(['status' => 'banned', 'ban_type' => 'temp', 'message' => 'حساب شما مسدود است.']);
            exit;
        }

        $deviceToken = bin2hex(random_bytes(32));
        setcookie('device_token', $deviceToken, time() + (86400 * 365), '/', '', false, true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $u;
        $_SESSION['sticker'] = $user['sticker'];
        
        $pdo->prepare("UPDATE users SET last_activity = NOW(), is_online = 1, user_agent = ?, ip_address = ?, device_token = ? WHERE id = ?")->execute([$ua, $ip, $deviceToken, $user['id']]);
        
        session_regenerate_id(true);
        $_SESSION['spam_check'] = [];
        $_SESSION['bw_nonce'] = bin2hex(random_bytes(16));
        $_SESSION['logout_nonce'] = bin2hex(random_bytes(16));
        
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($_POST['action'] === 'login') {
        if (!isset($_POST['captcha']) || $_POST['captcha'] != $_SESSION['captcha_result']) {
            echo json_encode(['status' => 'error', 'message' => 'کد امنیتی (کپچا) اشتباه است']);
            exit;
        }

        $u = trim(preg_replace('/\s+/', ' ', $_POST['username']));
        $u = htmlspecialchars(strip_tags($u), ENT_QUOTES, 'UTF-8');
        $ip = get_client_ip();

        $stmtIp = $pdo->prepare("SELECT id FROM banned_ips WHERE ip_address = ?");
        $stmtIp->execute([$ip]);
        if ($stmtIp->fetch()) {
            echo json_encode(['status' => 'banned', 'ban_type' => 'perm', 'message' => 'دسترسی دستگاه شما به دلیل تخلف مسدود شده است (بن دائمی)']);
            exit;
        }

        $isAdminLogin = false;
        
        if ($u === $admin_username) {
            if (!isset($_POST['password']) || $_POST['password'] !== $admin_password) {
                echo json_encode(['status' => 'error', 'message' => 'رمز عبور مدیریت اشتباه است']);
                exit;
            }
            $isAdminLogin = true;
        } else {
            $len = mb_strlen($u, 'UTF-8');
            if ($len < 3 || $len > 10) {
                 echo json_encode(['status' => 'error', 'message' => 'نام کاربری باید بین ۳ تا ۱۰ کاراکتر باشد']);
                 exit;
            }
            if (!preg_match('/^[a-zA-Z0-9\x{0600}-\x{06FF}\s]+$/u', $u)) {
                echo json_encode(['status' => 'error', 'message' => 'نام کاربری فقط می‌تواند شامل حروف فارسی، انگلیسی و اعداد باشد']);
                exit;
            }
        }

        $stmt = $pdo->prepare("SELECT id, is_banned_until, sticker, ip_address, user_agent, device_token, security_code FROM users WHERE username = ?");
        $stmt->execute([$u]);
        $user = $stmt->fetch();
        
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        $deviceToken = null;
        if (isset($_COOKIE['device_token'])) {
            $deviceToken = $_COOKIE['device_token'];
        } else {
            $deviceToken = bin2hex(random_bytes(32));
            setcookie('device_token', $deviceToken, time() + (86400 * 365), '/', '', false, true);
        }

        $generatedSecurityCode = null;

        if ($user) {
            if ($user['is_banned_until'] > time()) {
                $rem = $user['is_banned_until'] - time();
                $banType = 'temp';
                $msg = '';
                
                if ($rem > 31536000) { 
                     $banType = 'perm';
                     $msg = 'حساب شما دائماً مسدود شده است';
                } else {
                     $min = ceil($rem / 60);
                     $msg = "حساب شما به مدت $min دقیقه دیگر مسدود است";
                }
                echo json_encode(['status' => 'banned', 'ban_type' => $banType, 'message' => $msg]);
                exit;
            }
            
            if (!$isAdminLogin) {
                if ($user['ip_address'] !== $ip && $user['user_agent'] !== $ua) {
                    if ($user['device_token'] && $user['device_token'] !== $deviceToken) {
                        echo json_encode(['status' => 'device_mismatch', 'message' => 'این نام کاربری متعلق به دستگاه دیگری است. اگر کد امنیتی دارید روی دکمه "کد ورود امنیتی" کلیک کنید.']);
                        exit;
                    }
                }
            }
            
            if (empty($user['security_code']) && !$isAdminLogin) {
                $generatedSecurityCode = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
                $pdo->prepare("UPDATE users SET security_code = ? WHERE id = ?")->execute([$generatedSecurityCode, $user['id']]);
            }

            if ($isAdminLogin) {
                $adminSticker = '✔️';
                $pdo->prepare("UPDATE users SET sticker = ? WHERE id = ?")->execute([$adminSticker, $user['id']]);
                $_SESSION['sticker'] = $adminSticker;
            } elseif (empty($user['sticker'])) {
                $randSticker = $stickers[array_rand($stickers)];
                $pdo->prepare("UPDATE users SET sticker = ? WHERE id = ?")->execute([$randSticker, $user['id']]);
                $_SESSION['sticker'] = $randSticker;
            } else {
                $_SESSION['sticker'] = $user['sticker'];
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $u;
            
            $stmt = $pdo->prepare("SELECT id FROM messages ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $lastId = $stmt->fetchColumn();
            $lastId = $lastId ? $lastId : 0;
            
            $pdo->prepare("UPDATE users SET last_activity = NOW(), is_online = 1, user_agent = ?, ip_address = ?, last_notif_id = ?, device_token = ? WHERE id = ?")->execute([$ua, $ip, $lastId, $deviceToken, $user['id']]);
        } else {
            $stmtCheckLimit = $pdo->prepare("SELECT COUNT(*) FROM users WHERE ip_address = ? AND user_agent = ?");
            $stmtCheckLimit->execute([$ip, $ua]);
            $cnt = $stmtCheckLimit->fetchColumn();
            
            if ($cnt >= 3) {
                echo json_encode(['status' => 'error', 'message' => 'شما به سقف مجاز ساخت حساب (۳ عدد) رسیده‌اید. برای ورود از نام‌های قبلی خود استفاده کنید.']);
                exit;
            }

            if ($isAdminLogin) {
                $randSticker = '✔️';
            } else {
                $randSticker = $stickers[array_rand($stickers)];
            }
            
            $stmt = $pdo->prepare("SELECT id FROM messages ORDER BY id DESC LIMIT 1");
            $stmt->execute();
            $lastId = $stmt->fetchColumn();
            $lastId = $lastId ? $lastId : 0;
            
            $generatedSecurityCode = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            
            $stmt = $pdo->prepare("INSERT INTO users (username, sticker, user_agent, ip_address, is_online, last_notif_id, device_token, security_code) VALUES (?, ?, ?, ?, 1, ?, ?, ?)");
            $stmt->execute([$u, $randSticker, $ua, $ip, $lastId, $deviceToken, $generatedSecurityCode]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['username'] = $u;
            $_SESSION['sticker'] = $randSticker;
        }
        
        session_regenerate_id(true);

        $_SESSION['spam_check'] = [];
        $_SESSION['bw_nonce'] = bin2hex(random_bytes(16));
        $_SESSION['logout_nonce'] = bin2hex(random_bytes(16));
        
        if ($isAdminLogin) {
            setcookie('admin_token', hash('sha256', $admin_password . $ua . 'salt'), time() + 86400, '/', '', false, false);
        }

        echo json_encode([
            'status' => 'success',
            'security_code' => $generatedSecurityCode
        ]);
        exit;
    }
}
?>