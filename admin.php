<?php
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'عدم دسترسی']);
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>عدم دسترسی</title>
        <link rel="icon" type="image/png" href="<?php echo $favicon_path; ?>">
        <style>
            body { margin:0; height:100vh; display:flex; justify-content:center; align-items:center; background:#0f172a; color:#fff; font-family:sans-serif; }
            .error-box { text-align:center; padding:40px; background:#1e293b; border-radius:20px; border:1px solid #ef4444; box-shadow:0 10px 40px rgba(239,68,68,0.2); animation:fadeIn 0.5s ease; max-width:90%; width:400px; }
            .icon { font-size:60px; margin-bottom:20px; display:block; }
            h1 { margin:0 0 10px; color:#ef4444; }
            p { color:#94a3b8; margin-bottom:25px; }
            .btn { background:#334155; color:#fff; text-decoration:none; padding:10px 25px; border-radius:10px; transition:0.3s; display:inline-block; }
            .btn:hover { background:#475569; }
            @keyframes fadeIn { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
        </style>
    </head>
    <body>
        <div class="error-box">
            <span class="icon">🚫</span>
            <h1>دسترسی غیرمجاز</h1>
            <p>شما مجوز ورود به این بخش را ندارید.</p>
            <a href="index.php" class="btn">بازگشت به خانه</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if ($_SESSION['username'] !== $admin_username) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'شما ادمین نیستید']);
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>عدم دسترسی</title>
        <link rel="icon" type="image/png" href="<?php echo $favicon_path; ?>">
        <style>
            body { margin:0; height:100vh; display:flex; justify-content:center; align-items:center; background:#0f172a; color:#fff; font-family:sans-serif; }
            .error-box { text-align:center; padding:40px; background:#1e293b; border-radius:20px; border:1px solid #f59e0b; box-shadow:0 10px 40px rgba(245,158,11,0.2); animation:fadeIn 0.5s ease; max-width:90%; width:400px; }
            .icon { font-size:60px; margin-bottom:20px; display:block; }
            h1 { margin:0 0 10px; color:#f59e0b; }
            p { color:#94a3b8; margin-bottom:25px; }
            .btn { background:#334155; color:#fff; text-decoration:none; padding:10px 25px; border-radius:10px; transition:0.3s; display:inline-block; }
            .btn:hover { background:#475569; }
            @keyframes fadeIn { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
        </style>
    </head>
    <body>
        <div class="error-box">
            <span class="icon">⚠️</span>
            <h1>دسترسی محدود</h1>
            <p>فقط مدیر کل به این بخش دسترسی دارد.</p>
            <a href="index.php" class="btn">بازگشت به چت</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if (isset($_GET['backup']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="blackwacker_backup_' . date('Y-m-d_H-i-s') . '.sql"');
    
    $tables = ['users', 'rooms', 'room_invites', 'messages', 'settings', 'system_settings', 'user_reports', 'banned_ips', 'global_alerts'];
    $out = "-- BlackWalker Chat Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT * FROM $table");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out .= "TRUNCATE TABLE `$table`;\n";
            foreach ($rows as $row) {
                $keys = array_keys($row);
                $vals = array_map(function($v) use ($pdo) { return $v === null ? 'NULL' : $pdo->quote($v); }, array_values($row));
                $out .= "INSERT INTO `$table` (`" . implode("`,`", $keys) . "`) VALUES (" . implode(",", $vals) . ");\n";
            }
            $out .= "\n";
        } catch(Exception $e) {}
    }
    
    $out .= "SET FOREIGN_KEY_CHECKS=1;\n";
    echo $out;
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!isset($_POST['action']) || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status' => 'error', 'message' => 'نشست نامعتبر']);
        exit;
    }

    $action = $_POST['action'];

    try {
        if ($action === 'get_live_data') {
            $usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $msgsCount = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
            $roomsCount = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
            
            $rooms = $pdo->query("SELECT r.*, u.username as creator FROM rooms r LEFT JOIN users u ON r.created_by = u.id ORDER BY r.type='public' DESC, r.id DESC")->fetchAll(PDO::FETCH_ASSOC);
            
            $sql = "SELECT id, username, sticker, is_online, last_activity, ip_address, is_banned_until FROM users ORDER BY 
                    CASE WHEN username = :admin THEN 0 ELSE 1 END, 
                    is_online DESC, 
                    last_activity DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['admin' => $admin_username]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $processedUsers = [];
            foreach($users as $u) {
                $isAdmin = ($u['username'] === $admin_username);
                $isBanned = ($u['is_banned_until'] > time());
                $isOnline = ($u['is_online'] == 1 && strtotime($u['last_activity']) > time() - 300);
                
                $statusHtml = '<span class="badge badge-success">فعال</span>';
                if ($isBanned) {
                    $rem = $u['is_banned_until'] - time();
                    if ($rem > 31536000) {
                        $statusHtml = '<span class="badge badge-danger">مسدود (دائم)</span>';
                    } else {
                        $min = ceil($rem / 60);
                        $statusHtml = "<span class=\"badge badge-danger\">مسدود ($min دقیقه)</span>";
                    }
                }

                $u['is_admin'] = $isAdmin;
                $u['is_online_bool'] = $isOnline;
                $u['online_text'] = $isOnline ? 'آنلاین' : 'آفلاین';
                $u['time_ago'] = time_elapsed_string($u['last_activity']);
                $u['status_html'] = $statusHtml;
                $processedUsers[] = $u;
            }

            $processedRooms = [];
            foreach($rooms as $r) {
                $isDefault = ($r['name'] === 'گفتگوی عمومی');
                
                if ($r['type'] === 'public') {
                    $r['type_html'] = '<span class="badge badge-success">عمومی</span>';
                } else {
                    $r['type_html'] = '<span class="badge badge-info">خصوصی</span>';
                }
                
                $r['creator'] = $r['creator'] ?: 'سیستم';
                $r['is_default'] = $isDefault;
                $processedRooms[] = $r;
            }
            
            $stmtLock = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'lock_upload'");
            $stmtLock->execute();
            $isUploadLocked = ($stmtLock->fetchColumn() === '1');

            $stmtVoice = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'lock_voice'");
            $stmtVoice->execute();
            $isVoiceLocked = ($stmtVoice->fetchColumn() === '1');

            $stmtSiteLock = $pdo->prepare("SELECT value FROM settings WHERE name = 'site_lock'");
            $stmtSiteLock->execute();
            $isSiteLocked = ($stmtSiteLock->fetchColumn() === '1');
            
            $stmtReportLock = $pdo->prepare("SELECT value FROM settings WHERE name = 'report_lock'");
            $stmtReportLock->execute();
            $isReportLocked = ($stmtReportLock->fetchColumn() === '1');

            $stmtAR = $pdo->prepare("SELECT value FROM settings WHERE name = 'auto_reset_enabled'");
            $stmtAR->execute();
            $autoResetEnabled = ($stmtAR->fetchColumn() === '1');
            
            $stmtART = $pdo->prepare("SELECT value FROM settings WHERE name = 'auto_reset_target'");
            $stmtART->execute();
            $autoResetTarget = (int)$stmtART->fetchColumn();

            $stmtARR = $pdo->prepare("SELECT value FROM settings WHERE name = 'auto_reset_recurring'");
            $stmtARR->execute();
            $autoResetRecurring = ($stmtARR->fetchColumn() === '1');
            
            $autoResetText = 'غیرفعال';
            if ($autoResetEnabled && $autoResetTarget > time()) {
                $rem = $autoResetTarget - time();
                $h = floor($rem / 3600);
                $m = floor(($rem % 3600) / 60);
                $recText = $autoResetRecurring ? ' (تکرار ۲۴ ساعته)' : '';
                $autoResetText = "فعال: {$h} ساعت و {$m} دقیقه دیگر{$recText}";
            } elseif ($autoResetEnabled) {
                $autoResetText = "در حال اجرا...";
            }

            $reports = $pdo->query("SELECT ur.id, ur.reason, ur.created_at, u1.username as reporter, u2.username as reported, u2.id as reported_id FROM user_reports ur JOIN users u1 ON ur.reporter_id = u1.id JOIN users u2 ON ur.reported_id = u2.id ORDER BY ur.created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($reports as &$rep) {
                $rep['time_ago'] = time_elapsed_string($rep['created_at']);
            }

            echo json_encode([
                'status' => 'success',
                'users' => $processedUsers,
                'rooms' => $processedRooms,
                'reports' => $reports,
                'lock_upload' => $isUploadLocked,
                'lock_voice' => $isVoiceLocked,
                'site_lock' => $isSiteLocked,
                'report_lock' => $isReportLocked,
                'auto_reset_enabled' => $autoResetEnabled,
                'auto_reset_text' => $autoResetText,
                'auto_reset_recurring' => $autoResetRecurring,
                'stats' => [
                    'users' => number_format($usersCount),
                    'msgs' => number_format($msgsCount),
                    'rooms' => number_format($roomsCount)
                ]
            ]);
            exit;
        }
        elseif ($action === 'create_room') {
            $name = trim($_POST['name']);
            $type = $_POST['type'];
            
            if (empty($name)) throw new Exception('نام اتاق نمی‌تواند خالی باشد');
            
            $inviteCode = null;
            if ($type === 'private') {
                $inviteCode = bin2hex(random_bytes(4));
            }

            $stmt = $pdo->prepare("INSERT INTO rooms (name, type, created_by, invite_code) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $type, $_SESSION['user_id'], $inviteCode]);
            echo json_encode(['status' => 'success', 'message' => 'اتاق با موفقیت ساخته شد']);
        }
        elseif ($action === 'invite_user') {
            $roomName = $_POST['room_name'];
            $username = trim($_POST['username']);
            
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $uid = $stmt->fetchColumn();
            
            if (!$uid) throw new Exception('کاربر یافت نشد');
            
            $chk = $pdo->prepare("SELECT id FROM room_invites WHERE user_id = ? AND room_name = ?");
            $chk->execute([$uid, $roomName]);
            if ($chk->fetch()) throw new Exception('کاربر قبلاً دعوت شده است');
            
            $stmt = $pdo->prepare("INSERT INTO room_invites (user_id, room_name, invited_by) VALUES (?, ?, ?)");
            $stmt->execute([$uid, $roomName, $_SESSION['user_id']]);
            
            echo json_encode(['status' => 'success', 'message' => "کاربر $username با موفقیت دعوت شد"]);
        }
        elseif ($action === 'delete_room') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("SELECT name FROM rooms WHERE id = ?");
            $stmt->execute([$id]);
            $roomName = $stmt->fetchColumn();

            if (!$roomName) throw new Exception('اتاق یافت نشد');
            if ($roomName === 'گفتگوی عمومی') throw new Exception('اتاق عمومی اصلی قابل حذف نیست');

            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM messages WHERE room_name = ?")->execute([$roomName]);
            $pdo->prepare("DELETE FROM room_invites WHERE room_name = ?")->execute([$roomName]);
            $pdo->prepare("DELETE FROM rooms WHERE id = ?")->execute([$id]);
            $pdo->commit();

            echo json_encode(['status' => 'success', 'message' => 'اتاق با موفقیت حذف شد']);
        }
        elseif ($action === 'delete_all_custom_rooms') {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT name FROM rooms WHERE name != 'گفتگوی عمومی'");
            $stmt->execute();
            $roomsToDelete = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($roomsToDelete)) {
                $placeholders = implode(',', array_fill(0, count($roomsToDelete), '?'));
                $pdo->prepare("DELETE FROM messages WHERE room_name IN ($placeholders)")->execute($roomsToDelete);
                $pdo->prepare("DELETE FROM room_invites WHERE room_name IN ($placeholders)")->execute($roomsToDelete);
                $pdo->exec("DELETE FROM rooms WHERE name != 'گفتگوی عمومی'");
            }
            
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'تمام اتاق‌های اضافی حذف شدند']);
        }
        elseif ($action === 'delete_all_msgs_global') {
            $pdo->exec("TRUNCATE TABLE messages");
            echo json_encode(['status' => 'success', 'message' => 'تمام پیام‌های سیستم پاکسازی شد']);
        }
        elseif ($action === 'import_db') {
            if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('فایلی انتخاب نشده است یا آپلود با خطا مواجه شد');
            }
            $ext = strtolower(pathinfo($_FILES['backup_file']['name'], PATHINFO_EXTENSION));
            if ($ext !== 'sql') {
                throw new Exception('فقط فایل‌های SQL مجاز هستند');
            }
            
            $sqlContent = file_get_contents($_FILES['backup_file']['tmp_name']);
            if (empty($sqlContent)) throw new Exception('محتوای فایل خالی است');
            
            try {
                $pdo->exec($sqlContent);
                echo json_encode(['status' => 'success', 'message' => 'اطلاعات دیتابیس با موفقیت بازگردانی شد']);
            } catch(PDOException $e) {
                throw new Exception('خطا در اجرای کوئری‌ها: ' . $e->getMessage());
            }
        }
        elseif ($action === 'ban_user') {
            $userId = (int)$_POST['user_id'];
            $duration = isset($_POST['duration']) ? $_POST['duration'] : 'perm';
            
            if ($userId == $_SESSION['user_id']) throw new Exception('نمی‌توانید خودتان را مسدود کنید');

            $banTime = 0;
            if ($duration === '5') $banTime = time() + (5 * 60);
            elseif ($duration === '10') $banTime = time() + (10 * 60);
            else $banTime = time() + (365 * 24 * 3600); 

            $pdo->prepare("UPDATE users SET is_banned_until = ?, is_online = 0 WHERE id = ?")->execute([$banTime, $userId]);
            
            if ($duration === 'perm') {
                $stmt = $pdo->prepare("SELECT ip_address FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $ip = $stmt->fetchColumn();
                if ($ip) {
                    $pdo->prepare("INSERT IGNORE INTO banned_ips (ip_address) VALUES (?)")->execute([$ip]);
                }
            }
            
            echo json_encode(['status' => 'success', 'message' => 'کاربر مسدود شد']);
        }
        elseif ($action === 'unban_user') {
            $userId = (int)$_POST['user_id'];
            $pdo->prepare("UPDATE users SET is_banned_until = 0 WHERE id = ?")->execute([$userId]);
            $stmt = $pdo->prepare("SELECT ip_address FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $ip = $stmt->fetchColumn();
            if($ip) $pdo->prepare("DELETE FROM banned_ips WHERE ip_address = ?")->execute([$ip]);
            echo json_encode(['status' => 'success', 'message' => 'کاربر رفع مسدودیت شد']);
        }
        elseif ($action === 'global_reset') {
            $pass = $_POST['password'];
            if ($pass !== $admin_password) throw new Exception('رمز عبور اشتباه است');

            perform_system_reset($pdo);
            
            echo json_encode(['status' => 'success', 'message' => 'سیستم با موفقیت بازنشانی شد']);
        }
        elseif ($action === 'enable_auto_reset') {
            $hours = (int)$_POST['hours'];
            $recurring = isset($_POST['recurring']) && $_POST['recurring'] === '1' ? '1' : '0';
            
            if ($hours < 1) throw new Exception('حداقل زمان ۱ ساعت است');
            
            $targetTime = time() + ($hours * 3600);
            $pdo->prepare("UPDATE settings SET value = '1' WHERE name = 'auto_reset_enabled'")->execute();
            $pdo->prepare("UPDATE settings SET value = ? WHERE name = 'auto_reset_target'")->execute([$targetTime]);
            $pdo->prepare("UPDATE settings SET value = ? WHERE name = 'auto_reset_recurring'")->execute([$recurring]);
            
            $recMsg = $recurring === '1' ? ' (با تکرار ۲۴ ساعته)' : '';
            echo json_encode(['status' => 'success', 'message' => "ریست خودکار برای $hours ساعت دیگر تنظیم شد$recMsg"]);
        }
        elseif ($action === 'disable_auto_reset') {
            $pdo->prepare("UPDATE settings SET value = '0' WHERE name = 'auto_reset_enabled'")->execute();
            $pdo->prepare("UPDATE settings SET value = '0' WHERE name = 'auto_reset_target'")->execute();
            $pdo->prepare("UPDATE settings SET value = '0' WHERE name = 'auto_reset_recurring'")->execute();
            echo json_encode(['status' => 'success', 'message' => 'ریست خودکار لغو شد']);
        }
        elseif ($action === 'delete_user_msgs') {
            $userId = (int)$_POST['user_id'];
            $pdo->prepare("DELETE FROM messages WHERE sender_id = ?")->execute([$userId]);
            echo json_encode(['status' => 'success', 'message' => 'تمام پیام‌های کاربر حذف شد']);
        }
        elseif ($action === 'toggle_upload_lock') {
            $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'lock_upload'");
            $stmt->execute();
            $curr = $stmt->fetchColumn();
            $new = ($curr === '1') ? '0' : '1';
            $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'lock_upload'")->execute([$new]);
            echo json_encode(['status' => 'success', 'message' => ($new === '1' ? 'آپلود فایل غیرفعال شد' : 'آپلود فایل فعال شد')]);
        }
        elseif ($action === 'toggle_voice_lock') {
            $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'lock_voice'");
            $stmt->execute();
            $curr = $stmt->fetchColumn();
            $new = ($curr === '1') ? '0' : '1';
            $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'lock_voice'")->execute([$new]);
            echo json_encode(['status' => 'success', 'message' => ($new === '1' ? 'ویس غیرفعال شد' : 'ویس فعال شد')]);
        }
        elseif ($action === 'toggle_site_lock') {
            $stmt = $pdo->prepare("SELECT value FROM settings WHERE name = 'site_lock'");
            $stmt->execute();
            $curr = $stmt->fetchColumn();
            $new = ($curr === '1') ? '0' : '1';
            $pdo->prepare("UPDATE settings SET value = ? WHERE name = 'site_lock'")->execute([$new]);
            echo json_encode(['status' => 'success', 'message' => ($new === '1' ? 'سایت قفل شد' : 'سایت باز شد')]);
        }
        elseif ($action === 'toggle_report_lock') {
            $stmt = $pdo->prepare("SELECT value FROM settings WHERE name = 'report_lock'");
            $stmt->execute();
            $curr = $stmt->fetchColumn();
            $new = ($curr === '1') ? '0' : '1';
            $pdo->prepare("UPDATE settings SET value = ? WHERE name = 'report_lock'")->execute([$new]);
            echo json_encode(['status' => 'success', 'message' => ($new === '1' ? 'گزارش‌دهی قفل شد' : 'گزارش‌دهی آزاد شد')]);
        }
        elseif ($action === 'delete_report') {
            $id = (int)$_POST['id'];
            $pdo->prepare("DELETE FROM user_reports WHERE id = ?")->execute([$id]);
            echo json_encode(['status' => 'success', 'message' => 'گزارش حذف شد']);
        }
        elseif ($action === 'get_user_msgs') {
            $targetId = (int)$_POST['user_id'];
            $stmt = $pdo->prepare("SELECT m.*, u.username FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.sender_id = ? ORDER BY m.created_at DESC LIMIT 100");
            $stmt->execute([$targetId]);
            $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($msgs as &$m) {
                 if($m['msg_type'] === 'text') $m['message'] = decrypt_data($m['message']);
                 $ts = strtotime($m['created_at']);
                 $m['created_at'] = jdate("Y/m/d H:i", $ts);
            }
            echo json_encode(['status' => 'success', 'messages' => $msgs]);
        }
        elseif ($action === 'send_global_notif') {
            $msg = trim($_POST['message']);
            if(empty($msg)) throw new Exception('متن نوتیفیکیشن نمی‌تواند خالی باشد');
            
            $pdo->exec("CREATE TABLE IF NOT EXISTS global_alerts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                message TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            $stmt = $pdo->prepare("INSERT INTO global_alerts (message) VALUES (?)");
            $stmt->execute([$msg]);
            
            echo json_encode(['status' => 'success', 'message' => 'نوتیفیکیشن سراسری برای تمامی کاربران ارسال شد']);
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>پنل مدیریت حرفه‌ای</title>
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <link rel="icon" type="image/png" href="<?php echo $favicon_path; ?>">
    <style>
        @font-face { font-family: 'AppFont'; src: url('fonts/<?php echo $font_text; ?>.ttf') format('truetype'); font-weight: normal; }
        @font-face { font-family: 'AppHeading'; src: url('fonts/<?php echo $font_heading; ?>.ttf') format('truetype'); font-weight: bold; }
        
        :root { --bg: #0f172a; --panel: #1e293b; --border: #334155; --primary: #6366f1; --primary-hover: #4f46e5; --danger: #ef4444; --success: #10b981; --text: #f8fafc; --text-muted: #94a3b8; }
        
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { background-color: var(--bg); color: var(--text); font-family: 'AppFont', Tahoma, sans-serif; margin: 0; padding: 0; padding-bottom: 80px; overflow-x: hidden; }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        
        .header { background: rgba(30, 41, 59, 0.95); backdrop-filter: blur(20px); border-bottom: 1px solid var(--border); padding: 15px 30px; position: sticky; top: 0; z-index: 100; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 40px -10px rgba(0,0,0,0.5); border-radius: 0 0 25px 25px; margin-bottom: 30px; }
        .header h1 { margin: 0; font-size: 1.4rem; color: #fff; font-family: 'AppHeading'; text-shadow: 0 0 20px rgba(99,102,241,0.5); }
        
        .btn { padding: 12px 20px; border-radius: 14px; border: none; cursor: pointer; color: #fff; font-family: 'AppFont'; font-size: 0.95rem; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; justify-content: center; font-weight: bold; white-space: nowrap; box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .btn:active { transform: scale(0.96); }
        .btn-primary { background: linear-gradient(135deg, var(--primary), #818cf8); }
        .btn-primary:hover { box-shadow: 0 8px 25px rgba(99,102,241,0.4); }
        .btn-danger { background: linear-gradient(135deg, var(--danger), #f87171); }
        .btn-danger:hover { box-shadow: 0 8px 25px rgba(239,68,68,0.4); }
        .btn-ghost { background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: var(--text-muted); box-shadow: none; }
        .btn-ghost:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .btn-success { background: linear-gradient(135deg, var(--success), #34d399); box-shadow: 0 5px 15px rgba(16, 185, 129, 0.2); }
        .btn-success:hover { box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4); }
        .btn-sm { padding: 6px 14px; font-size: 0.8rem; border-radius: 10px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: linear-gradient(145deg, #1e293b, #172033); border: 1px solid var(--border); padding: 25px; border-radius: 20px; text-align: center; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.2); transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); border-color: var(--primary); }
        .stat-value { font-size: 2.2rem; font-family: 'AppHeading'; display: block; margin-bottom: 5px; background: linear-gradient(to right, #fff, #94a3b8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-label { font-size: 0.9rem; color: var(--text-muted); letter-spacing: 0.5px; }

        .card { background: #1e293b; border: 1px solid var(--border); border-radius: 24px; padding: 30px; margin-bottom: 25px; overflow: hidden; position: relative; box-shadow: 0 20px 50px -10px rgba(0,0,0,0.3); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 20px; }
        .card-title { margin: 0; font-size: 1.3rem; border-right: 5px solid var(--primary); padding-right: 15px; color: #fff; font-family: 'AppHeading'; }

        .action-box { background: rgba(0,0,0,0.2); border-radius: 18px; padding: 20px; margin-bottom: 20px; border: 1px dashed var(--border); display: flex; flex-direction: column; gap: 15px; }
        .action-box h4 { margin: 0 0 10px; color: var(--text-muted); font-size: 0.95rem; }

        .form-control { background: #0f172a; border: 1px solid var(--border); color: var(--text); padding: 15px; border-radius: 14px; width: 100%; outline: none; font-family: inherit; transition: 0.3s; font-size: 1rem; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15); }
        
        .input-group { display: flex; gap: 12px; flex-wrap: wrap; }
        .input-group > * { flex: 1; min-width: 200px; }
        .input-group button { flex: 0 0 auto; }

        .table-responsive { overflow-x: auto; border-radius: 18px; border: 1px solid var(--border); background: #0f172a; -ms-overflow-style: none; scrollbar-width: none; }
        .table-responsive::-webkit-scrollbar { display: none; }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th, td { padding: 18px; text-align: right; border-bottom: 1px solid rgba(255,255,255,0.05); }
        th { background: rgba(255,255,255,0.03); color: var(--text-muted); font-weight: normal; font-size: 0.9rem; letter-spacing: 0.5px; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,0.02); }
        
        .badge { padding: 6px 12px; border-radius: 10px; font-size: 0.8rem; display: inline-block; font-weight: bold; }
        .badge-success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.2); }
        .badge-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.2); }
        .badge-info { background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.2); }
        .badge-secondary { background: rgba(255, 255, 255, 0.1); color: #cbd5e1; border: 1px solid rgba(255, 255, 255, 0.2); }
        
        .status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-left: 8px; position: relative; }
        .status-dot.online { background: var(--success); box-shadow: 0 0 10px var(--success); }
        .status-dot.online::after { content:''; position:absolute; inset:-4px; border-radius:50%; border:1px solid var(--success); opacity:0.5; animation: ping 1.5s infinite; }
        .status-dot.offline { background: var(--text-muted); opacity: 0.5; }
        @keyframes ping { 0%{transform:scale(0.8);opacity:0.8} 100%{transform:scale(2);opacity:0} }

        .modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(10px); z-index: 2000; display: none; justify-content: center; align-items: center; padding: 20px; }
        .modal-content { background: linear-gradient(145deg, #1e293b, #172033); width: 100%; max-width: 450px; padding: 40px; border-radius: 30px; border: 1px solid rgba(255,255,255,0.1); text-align: center; animation: popIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow: 0 25px 80px rgba(0,0,0,0.6); display:flex; flex-direction:column; max-height:85vh; }
        @keyframes popIn { from { transform: scale(0.9) translateY(20px); opacity: 0; } to { transform: scale(1) translateY(0); opacity: 1; } }
        .modal-icon { font-size: 4rem; margin-bottom: 20px; display: block; filter: drop-shadow(0 0 20px rgba(255,255,255,0.2)); }

        .danger-zone { border: 1px dashed var(--danger); background: rgba(239, 68, 68, 0.02); }
        .danger-card-title { color: var(--danger); border-color: var(--danger); }
        
        #toast { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%) translateY(100px); background: rgba(16, 185, 129, 0.95); backdrop-filter: blur(5px); color: #fff; padding: 12px 25px; border-radius: 50px; z-index: 3000; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); font-weight: bold; box-shadow: 0 10px 30px rgba(0,0,0,0.4); display:flex; align-items:center; gap:10px; font-size: 0.95rem; border: 1px solid rgba(255,255,255,0.2); width: max-content; max-width: 90%; white-space: nowrap; }
        #toast.show { transform: translateX(-50%) translateY(0); }
        #toast.error { background: rgba(239, 68, 68, 0.95); }
        
        .msg-list { overflow-y:auto; text-align:right; margin-bottom:20px; padding:10px; border:1px solid var(--border); border-radius:15px; background:rgba(0,0,0,0.2); flex:1; -ms-overflow-style: none; scrollbar-width: none; }
        .msg-list::-webkit-scrollbar { display: none; }
        .admin-msg-item { background:rgba(255,255,255,0.05); padding:10px; margin-bottom:8px; border-radius:10px; font-size:0.9rem; border:1px solid rgba(255,255,255,0.05); }
        .admin-msg-meta { font-size:0.75rem; color:var(--text-muted); margin-bottom:5px; display:flex; justify-content:space-between; }

        .settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; }
        
        #paginationControls .btn { transition: 0.2s; }
        #paginationControls .btn-primary { background: var(--primary); color: #fff; box-shadow: 0 2px 10px rgba(99,102,241,0.3); border-color: var(--primary); }

        @media (max-width: 768px) {
            .header { padding: 15px; margin-bottom: 20px; border-radius: 0 0 20px 20px; flex-direction: column; gap: 10px; text-align: center; }
            .header h1 { font-size: 1.1rem; }
            .btn-header span { display: none; }
            .card { padding: 20px; border-radius: 20px; }
            .action-box { padding: 15px; }
            .btn { width: 100%; }
            .input-group > * { min-width: 100%; }
            .stat-value { font-size: 1.8rem; }
            #toast { bottom: 20px; font-size: 0.85rem; padding: 10px 20px; }
        }
    </style>
</head>
<body>

<div id="toast"></div>

<div id="confirmModal" class="modal-overlay">
    <div class="modal-content" style="max-height:auto">
        <span class="modal-icon">🤔</span>
        <h3 style="margin-top:0; font-size:1.5rem; color:#fff">تایید عملیات</h3>
        <p id="confirmText" style="color:var(--text-muted); margin-bottom:30px; line-height:1.7; font-size:1.1rem"></p>
        <div style="display:flex; gap:15px;">
            <button id="confirmYes" class="btn btn-primary" style="flex:1; padding:15px;">بله، انجام شود</button>
            <button onclick="closeModal('confirmModal')" class="btn btn-ghost" style="flex:1; padding:15px;">انصراف</button>
        </div>
    </div>
</div>

<div id="banModalOptions" class="modal-overlay">
    <div class="modal-content" style="max-height:auto">
        <span class="modal-icon">🚫</span>
        <h3 style="margin-top:0; font-size:1.5rem; color:#ef4444">مسدودسازی کاربر</h3>
        <p style="color:var(--text-muted); font-size:1rem; margin-bottom:20px">کاربر: <b id="banUserName" style="color:#fff"></b></p>
        <input type="hidden" id="banUserId">
        <div style="margin-bottom:25px; text-align:right;">
            <label style="color:#cbd5e1; font-size:0.9rem; margin-bottom:8px; display:block;">مدت زمان مسدودیت:</label>
            <select id="banDuration" class="form-control" style="background:#0f172a">
                <option value="5">۵ دقیقه</option>
                <option value="10">۱۰ دقیقه</option>
                <option value="perm">دائمی (۱ سال)</option>
            </select>
        </div>
        <div style="display:flex; gap:15px;">
            <button onclick="submitBan()" class="btn btn-danger" style="flex:1; padding:15px;">اعمال مسدودیت</button>
            <button onclick="closeModal('banModalOptions')" class="btn btn-ghost" style="flex:1; padding:15px;">لغو</button>
        </div>
    </div>
</div>

<div id="inviteModal" class="modal-overlay">
    <div class="modal-content" style="max-height:auto">
        <span class="modal-icon">📩</span>
        <h3 style="margin-top:0; font-size:1.5rem; color:#fff">دعوت کاربر</h3>
        <p style="color:var(--text-muted); font-size:1rem; margin-bottom:25px">برای دعوت به اتاق خصوصی، نام کاربری را وارد کنید</p>
        <input type="hidden" id="inviteRoomName">
        <input type="text" id="inviteUsername" class="form-control" placeholder="نام کاربری..." style="margin-bottom:25px; text-align:center; font-size:1.2rem; background:#0f172a">
        <div style="display:flex; gap:15px;">
            <button onclick="submitInvite()" class="btn btn-primary" style="flex:1; padding:15px;">ارسال دعوت‌نامه</button>
            <button onclick="closeModal('inviteModal')" class="btn btn-ghost" style="flex:1; padding:15px;">لغو</button>
        </div>
    </div>
</div>

<div id="msgViewModal" class="modal-overlay">
    <div class="modal-content" style="max-width:600px;">
        <h3 style="margin-top:0; margin-bottom:15px; font-size:1.2rem; color:#fff" id="msgViewTitle">پیام‌های کاربر</h3>
        <div class="msg-list" id="msgViewList"></div>
        <button onclick="closeModal('msgViewModal')" class="btn btn-ghost" style="width:100%; padding:15px;">بستن</button>
    </div>
</div>

<div class="header">
    <h1>پنل مدیریت پیشرفته</h1>
    <a href="index.php" class="btn btn-ghost btn-header"><span>بازگشت به چت</span> ↩</a>
</div>

<div class="container">
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-value" id="statUsers">...</span>
            <span class="stat-label">کاربران ثبت‌نامی</span>
        </div>
        <div class="stat-card">
            <span class="stat-value" id="statMsgs">...</span>
            <span class="stat-label">کل پیام‌های ارسالی</span>
        </div>
        <div class="stat-card">
            <span class="stat-value" id="statRooms">...</span>
            <span class="stat-label">اتاق‌های فعال</span>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">مدیریت دیتابیس (بکاپ و بازگردانی)</h2>
        </div>
        <div class="settings-grid">
            <div class="action-box">
                <h4>تهیه نسخه پشتیبان (Backup)</h4>
                <p style="font-size:0.85rem; color:var(--text-muted); margin-top:0;">دریافت فایل حاوی تمام اطلاعات فعلی سیستم.</p>
                <button onclick="downloadBackup()" class="btn btn-success" style="width:100%">📥 دانلود فایل بکاپ (.sql)</button>
            </div>
            <div class="action-box">
                <h4>بازگردانی دیتابیس (Import)</h4>
                <p style="font-size:0.85rem; color:var(--text-muted); margin-top:0;">انتخاب فایل .sql و جایگزینی کامل اطلاعات.</p>
                <div class="input-group">
                    <input type="file" id="backupFileInput" class="form-control" accept=".sql" style="padding:10px">
                    <button onclick="importBackup()" class="btn btn-danger">آپلود و ایمپورت 📤</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">تنظیمات سیستمی و قفل‌ها</h2>
        </div>
        <div class="settings-grid">
            <div class="action-box" style="flex-direction:row; align-items:center; justify-content:space-between; flex-wrap:wrap;">
                <div>
                    <h4 style="margin:0; color:#fff;">قفل فایل (File Upload)</h4>
                    <div id="lockStatusText" style="font-size:0.9rem; color:var(--text-muted); margin-top:5px">در حال بررسی...</div>
                </div>
                <button id="lockBtn" onclick="toggleLock()" class="btn btn-primary">تغییر وضعیت</button>
            </div>
            
            <div class="action-box" style="flex-direction:row; align-items:center; justify-content:space-between; flex-wrap:wrap;">
                <div>
                    <h4 style="margin:0; color:#fff;">قفل ویس (Voice)</h4>
                    <div id="voiceLockStatusText" style="font-size:0.9rem; color:var(--text-muted); margin-top:5px">در حال بررسی...</div>
                </div>
                <button id="voiceLockBtn" onclick="toggleVoiceLock()" class="btn btn-primary">تغییر وضعیت</button>
            </div>

            <div class="action-box" style="flex-direction:row; align-items:center; justify-content:space-between; flex-wrap:wrap;">
                <div>
                    <h4 style="margin:0; color:#fff;">قفل گزارش کاربر (Report)</h4>
                    <div id="reportLockStatusText" style="font-size:0.9rem; color:var(--text-muted); margin-top:5px">در حال بررسی...</div>
                </div>
                <button id="reportLockBtn" onclick="toggleReportLock()" class="btn btn-primary">تغییر وضعیت</button>
            </div>

            <div class="action-box" style="flex-direction:row; align-items:center; justify-content:space-between; flex-wrap:wrap; border-color:rgba(239, 68, 68, 0.3); grid-column: 1 / -1;">
                <div>
                    <h4 style="margin:0; color:#fff;">وضعیت قفل کل سایت (Maintenance)</h4>
                    <div id="siteLockStatusText" style="font-size:0.9rem; color:var(--text-muted); margin-top:5px">در حال بررسی...</div>
                </div>
                <button id="siteLockBtn" onclick="toggleSiteLock()" class="btn btn-danger">تغییر وضعیت</button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">زمان‌بندی ریست خودکار</h2>
        </div>
        <div class="action-box">
            <h4>تنظیم زمان پاکسازی کامل سیستم (بر حسب ساعت)</h4>
            <p style="font-size:0.85rem; color:var(--text-muted); margin-top:0;">پس از اتمام زمان، تمام اطلاعات (پیام‌ها، اتاق‌ها، فایل‌ها و کاربران به جز ادمین) حذف خواهند شد.</p>
            <div id="autoResetStatus" style="margin-bottom:10px; font-weight:bold; color:#f59e0b">وضعیت: ...</div>
            <div class="input-group">
                <input type="number" id="autoResetHours" class="form-control" placeholder="مثلا 72 (ساعت)" min="1">
                <button onclick="enableAutoReset()" class="btn btn-success">فعال‌سازی تایمر</button>
                <button onclick="disableAutoReset()" class="btn btn-danger">لغو تایمر</button>
            </div>
            <div style="margin-top:10px; display:flex; align-items:center; gap:10px; color:#cbd5e1;">
                <input type="checkbox" id="autoResetRecurring" style="width:18px; height:18px; cursor:pointer;">
                <label for="autoResetRecurring" style="cursor:pointer; font-size:0.9rem;">تکرار هر ۲۴ ساعت (پس از انجام ریست، تایمر برای فردا تنظیم می‌شود)</label>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">ارسال نوتیفیکیشن سراسری</h2>
        </div>
        <div class="action-box">
            <h4>متن پیام (برای تمام کاربران نمایش داده خواهد شد)</h4>
            <div class="input-group">
                <input type="text" id="globalNotifMsg" class="form-control" placeholder="متن اطلاعیه مهم...">
                <button onclick="sendGlobalNotif()" class="btn btn-primary">ارسال به همه 📢</button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">مدیریت گزارش‌ها</h2>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>گزارش‌دهنده</th>
                        <th>کاربر متخلف</th>
                        <th>دلیل</th>
                        <th>زمان</th>
                        <th width="150">عملیات</th>
                    </tr>
                </thead>
                <tbody id="reportsList"></tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">مدیریت اتاق‌ها</h2>
        </div>
        
        <div class="action-box">
            <h4>ساخت اتاق جدید</h4>
            <div class="input-group">
                <input type="text" id="roomName" class="form-control" placeholder="نام اتاق...">
                <select id="roomType" class="form-control" style="flex:0.5;">
                    <option value="private">🔒 خصوصی</option>
                    <option value="public">🌐 عمومی</option>
                </select>
                <button onclick="createRoom()" class="btn btn-primary">ساختن ✚</button>
            </div>
        </div>

        <div class="action-box" style="background:rgba(239, 68, 68, 0.05); border-color:rgba(239, 68, 68, 0.2)">
            <h4>حذف گروهی</h4>
            <button onclick="confirmAction('delete_all_custom_rooms', 0, 'آیا مطمئن هستید؟ تمام اتاق‌های عمومی و خصوصی (به جز اتاق اصلی) و تمام پیام‌های آن‌ها حذف خواهند شد.')" class="btn btn-danger" style="width:100%">
                🗑 حذف یکجای تمام اتاق‌های اضافی
            </button>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>نام اتاق</th>
                        <th>نوع</th>
                        <th>سازنده</th>
                        <th width="180">عملیات</th>
                    </tr>
                </thead>
                <tbody id="roomsList"></tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">کاربران و اعضا</h2>
            <div style="font-size:0.85rem; color:var(--text-muted); display:flex; align-items:center;">
                <span class="status-dot online" style="margin-left:8px;"></span> وضعیت زنده
            </div>
        </div>
        
        <div class="action-box">
            <h4>جستجوی کاربر</h4>
            <input type="text" id="searchUser" class="form-control" placeholder="نام کاربری را وارد کنید..." onkeyup="filterUsers()">
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>کاربر</th>
                        <th>وضعیت آنلاین</th>
                        <th>IP</th>
                        <th>وضعیت حساب</th>
                        <th width="180">عملیات</th>
                    </tr>
                </thead>
                <tbody id="usersList"></tbody>
            </table>
        </div>
        <div id="paginationControls" style="display:flex; justify-content:center; flex-wrap:wrap; gap:5px; margin-top:20px;"></div>
    </div>

    <div class="card danger-zone">
        <div class="card-header">
            <h2 class="card-title danger-card-title">ناحیه خطرناک</h2>
        </div>
        
        <div class="action-box">
            <h4>پاکسازی دیتابیس</h4>
            <button onclick="confirmAction('delete_all_msgs_global', 0, 'هشدار: تمام پیام‌های کل سیستم برای همیشه حذف خواهند شد!')" class="btn btn-danger" style="width:100%">حذف تمام پیام‌های سرور</button>
        </div>

        <div class="action-box">
            <h4>ریست فکتوری (بازگشت به کارخانه)</h4>
            <div class="input-group">
                <input type="password" id="resetPass" class="form-control" placeholder="رمز عبور مدیریت جهت تایید...">
                <button onclick="globalReset()" class="btn btn-danger">انجام ریست کامل</button>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const adminUsername = '<?php echo $admin_username; ?>';

let allUsersCache = [];
let currentPage = 1;
let usersPerPage = 10;

function showToast(msg, type='success') {
    const t = document.getElementById('toast');
    t.innerHTML = (type === 'error' ? '✖ ' : '✔ ') + msg;
    t.className = type === 'error' ? 'error show' : 'show';
    setTimeout(() => t.className = '', 3000);
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

function confirmAction(action, id, text) {
    const m = document.getElementById('confirmModal');
    document.getElementById('confirmText').textContent = text;
    m.style.display = 'flex';
    document.getElementById('confirmYes').onclick = () => {
        closeModal('confirmModal');
        performAction(action, id);
    };
}

function openBanModal(id, name) {
    document.getElementById('banUserId').value = id;
    document.getElementById('banUserName').textContent = name;
    document.getElementById('banModalOptions').style.display = 'flex';
}

function submitBan() {
    const id = document.getElementById('banUserId').value;
    const dur = document.getElementById('banDuration').value;
    const name = document.getElementById('banUserName').textContent;
    
    closeModal('banModalOptions');
    performAction('ban_user', 0, {user_id: id, duration: dur});
}

function openInviteModal(roomName) {
    document.getElementById('inviteRoomName').value = roomName;
    document.getElementById('inviteUsername').value = '';
    document.getElementById('inviteModal').style.display = 'flex';
    document.getElementById('inviteUsername').focus();
}

function performAction(action, id, extraData = {}) {
    const fd = new FormData();
    fd.append('action', action);
    fd.append('csrf_token', csrfToken);
    
    if (id) fd.append(action.includes('user') || action === 'get_user_msgs' ? 'user_id' : 'id', id);
    for (let k in extraData) fd.append(k, extraData[k]);

    fetch('admin.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.status === 'success') {
                showToast(d.message);
                refreshData(); 
            } else {
                showToast(d.message, 'error');
            }
        })
        .catch(() => showToast('خطای شبکه', 'error'));
}

function downloadBackup() {
    window.location.href = 'admin.php?backup=1&csrf_token=' + csrfToken;
}

function importBackup() {
    const fileInput = document.getElementById('backupFileInput');
    if(fileInput.files.length === 0) return showToast('لطفاً یک فایل .sql انتخاب کنید', 'error');
    
    confirmAction('import_db', 0, 'هشدار: اطلاعات فعلی دیتابیس به طور کامل با محتویات این فایل جایگزین خواهد شد. ادامه می‌دهید؟');
    document.getElementById('confirmYes').onclick = () => {
        closeModal('confirmModal');
        const fd = new FormData();
        fd.append('action', 'import_db');
        fd.append('csrf_token', csrfToken);
        fd.append('backup_file', fileInput.files[0]);
        
        fetch('admin.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.status === 'success') {
                    showToast(d.message);
                    fileInput.value = '';
                    refreshData(); 
                } else {
                    showToast(d.message, 'error');
                }
            })
            .catch(() => showToast('خطای شبکه', 'error'));
    };
}

function createRoom() {
    const name = document.getElementById('roomName').value;
    const type = document.getElementById('roomType').value;
    if(!name) return showToast('لطفاً نام اتاق را وارد کنید', 'error');
    performAction('create_room', 0, {name: name, type: type});
    document.getElementById('roomName').value = '';
}

function submitInvite() {
    const room = document.getElementById('inviteRoomName').value;
    const user = document.getElementById('inviteUsername').value;
    if(!user) return showToast('لطفاً نام کاربری را وارد کنید', 'error');
    
    closeModal('inviteModal');
    performAction('invite_user', 0, {room_name: room, username: user});
}

function globalReset() {
    const pass = document.getElementById('resetPass').value;
    if(!pass) return showToast('رمز عبور الزامی است', 'error');
    confirmAction('global_reset', 0, 'هشدار نهایی: کل دیتابیس (کاربران، اتاق‌های شخصی، پیام‌ها) پاک می‌شود!');
    document.getElementById('confirmYes').onclick = () => {
        closeModal('confirmModal');
        performAction('global_reset', 0, {password: pass});
    };
}

function enableAutoReset() {
    const h = document.getElementById('autoResetHours').value;
    const recurring = document.getElementById('autoResetRecurring').checked ? 1 : 0;
    if(!h || h < 1) return showToast('لطفاً ساعت را وارد کنید', 'error');
    performAction('enable_auto_reset', 0, {hours: h, recurring: recurring});
}

function disableAutoReset() {
    performAction('disable_auto_reset', 0);
}

function toggleLock() {
    performAction('toggle_upload_lock', 0);
}

function toggleVoiceLock() {
    performAction('toggle_voice_lock', 0);
}

function toggleSiteLock() {
    performAction('toggle_site_lock', 0);
}

function toggleReportLock() {
    performAction('toggle_report_lock', 0);
}

function sendGlobalNotif() {
    const msg = document.getElementById('globalNotifMsg').value;
    if(!msg) return showToast('متن پیام خالی است', 'error');
    
    confirmAction('send_global_notif', 0, 'آیا از ارسال این پیام به تمام کاربران مطمئن هستید؟');
    document.getElementById('confirmYes').onclick = () => {
        closeModal('confirmModal');
        performAction('send_global_notif', 0, {message: msg});
        document.getElementById('globalNotifMsg').value = '';
    };
}

function viewUserMsgs(id, name) {
    const fd = new FormData();
    fd.append('action', 'get_user_msgs');
    fd.append('user_id', id);
    fd.append('csrf_token', csrfToken);
    
    document.getElementById('msgViewList').innerHTML = '<div style="text-align:center;color:#94a3b8">در حال بارگذاری...</div>';
    document.getElementById('msgViewTitle').textContent = 'پیام‌های ' + name;
    document.getElementById('msgViewModal').style.display = 'flex';
    
    fetch('admin.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if(d.status === 'success') {
            let html = '';
            if(d.messages.length > 0) {
                d.messages.forEach(m => {
                    const typeIcon = m.msg_type === 'text' ? '📝' : (m.msg_type === 'voice' ? '🎤' : '📁');
                    const content = m.msg_type === 'text' ? m.message : (m.msg_type === 'voice' ? 'پیام صوتی' : 'فایل: ' + m.file_name);
                    html += `<div class="admin-msg-item">
                        <div class="admin-msg-meta"><span>${m.room_name || 'خصوصی'}</span><span>${m.created_at}</span></div>
                        <div style="color:#fff">${typeIcon} ${content}</div>
                    </div>`;
                });
            } else {
                html = '<div style="text-align:center;color:#94a3b8;padding:20px">هیچ پیامی یافت نشد</div>';
            }
            document.getElementById('msgViewList').innerHTML = html;
        } else {
            showToast(d.message, 'error');
        }
    });
}

function refreshData() {
    const fd = new FormData();
    fd.append('action', 'get_live_data');
    fd.append('csrf_token', csrfToken);
    
    fetch('admin.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if(d.status === 'success') {
                document.getElementById('statUsers').textContent = d.stats.users;
                document.getElementById('statMsgs').textContent = d.stats.msgs;
                document.getElementById('statRooms').textContent = d.stats.rooms;
                
                const lockBtn = document.getElementById('lockBtn');
                const lockText = document.getElementById('lockStatusText');
                if (d.lock_upload) {
                    lockBtn.textContent = 'باز کردن قفل فایل';
                    lockBtn.className = 'btn btn-success'; 
                    lockBtn.style.background = '#10b981';
                    lockText.innerHTML = '<span style="color:#ef4444">⛔ ارسال فایل قفل است</span>';
                } else {
                    lockBtn.textContent = 'قفل کردن فایل';
                    lockBtn.className = 'btn btn-danger';
                    lockBtn.style.background = ''; 
                    lockText.innerHTML = '<span style="color:#10b981">✔ ارسال فایل آزاد است</span>';
                }

                const voiceLockBtn = document.getElementById('voiceLockBtn');
                const voiceLockText = document.getElementById('voiceLockStatusText');
                if (d.lock_voice) {
                    voiceLockBtn.textContent = 'باز کردن قفل ویس';
                    voiceLockBtn.className = 'btn btn-success'; 
                    voiceLockBtn.style.background = '#10b981';
                    voiceLockText.innerHTML = '<span style="color:#ef4444">⛔ ارسال ویس قفل است</span>';
                } else {
                    voiceLockBtn.textContent = 'قفل کردن ویس';
                    voiceLockBtn.className = 'btn btn-danger';
                    voiceLockBtn.style.background = ''; 
                    voiceLockText.innerHTML = '<span style="color:#10b981">✔ ارسال ویس آزاد است</span>';
                }

                const reportLockBtn = document.getElementById('reportLockBtn');
                const reportLockText = document.getElementById('reportLockStatusText');
                if (d.report_lock) {
                    reportLockBtn.textContent = 'باز کردن قفل گزارش';
                    reportLockBtn.className = 'btn btn-success'; 
                    reportLockBtn.style.background = '#10b981';
                    reportLockText.innerHTML = '<span style="color:#ef4444">⛔ گزارش‌دهی قفل است</span>';
                } else {
                    reportLockBtn.textContent = 'قفل کردن گزارش';
                    reportLockBtn.className = 'btn btn-danger';
                    reportLockBtn.style.background = ''; 
                    reportLockText.innerHTML = '<span style="color:#10b981">✔ گزارش‌دهی فعال است</span>';
                }

                const siteLockBtn = document.getElementById('siteLockBtn');
                const siteLockText = document.getElementById('siteLockStatusText');
                if (d.site_lock) {
                    siteLockBtn.textContent = 'باز کردن سایت';
                    siteLockBtn.className = 'btn btn-success';
                    siteLockBtn.style.background = '#10b981';
                    siteLockText.innerHTML = '<span style="color:#ef4444">⛔ سایت قفل و از دسترس خارج است</span>';
                } else {
                    siteLockBtn.textContent = 'قفل کردن سایت';
                    siteLockBtn.className = 'btn btn-danger';
                    siteLockBtn.style.background = '';
                    siteLockText.innerHTML = '<span style="color:#10b981">✔ سایت در دسترس است</span>';
                }

                document.getElementById('autoResetStatus').textContent = 'وضعیت: ' + d.auto_reset_text;
                if(d.auto_reset_recurring) {
                    document.getElementById('autoResetRecurring').checked = true;
                }

                updateRooms(d.rooms);
                allUsersCache = d.users;
                renderUsers();
                updateReports(d.reports);
            }
        });
}

function updateRooms(rooms) {
    const tbody = document.getElementById('roomsList');
    let html = '';
    rooms.forEach(r => {
        let btn = '';
        if(r.is_default) {
            btn = '<span class="badge badge-secondary">اصلی</span>';
        } else {
            let invite = r.type === 'private' ? `<button onclick="openInviteModal('${r.name}')" class="btn btn-primary btn-sm" style="margin-left:5px">دعوت</button>` : '';
            btn = `${invite}<button onclick="confirmAction('delete_room', ${r.id}, 'آیا از حذف اتاق ${r.name} مطمئن هستید؟')" class="btn btn-danger btn-sm">حذف</button>`;
        }
        
        let creatorInfo = r.creator;
        if(r.creator !== 'سیستم') {
            creatorInfo = `<span style="color:#a5b4fc">${r.creator}</span>`;
        }
        
        html += `<tr id="room-${r.id}">
            <td><b>${r.name}</b></td>
            <td>${r.type_html}</td>
            <td>${creatorInfo}</td>
            <td><div style="display:flex; justify-content:flex-end;">${btn}</div></td>
        </tr>`;
    });
    if(tbody.innerHTML !== html) tbody.innerHTML = html;
}

function updateReports(reports) {
    const tbody = document.getElementById('reportsList');
    if (!reports || reports.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:#94a3b8">هیچ گزارشی ثبت نشده است</td></tr>';
        return;
    }
    let html = '';
    reports.forEach(r => {
        html += `<tr id="report-${r.id}">
            <td style="color:#a5b4fc">${r.reporter}</td>
            <td style="color:#f87171; font-weight:bold">${r.reported}</td>
            <td><div style="max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis" title="${r.reason}">${r.reason}</div></td>
            <td style="font-size:0.8rem; color:#94a3b8">${r.time_ago}</td>
            <td>
                <div style="display:flex; justify-content:flex-end; gap:5px">
                    <button onclick="openBanModal(${r.reported_id}, '${r.reported}')" class="btn btn-danger btn-sm">مسدود</button>
                    <button onclick="confirmAction('delete_report', ${r.id}, 'حذف گزارش؟')" class="btn btn-ghost btn-sm" style="color:#ef4444; border-color:#ef4444">✘</button>
                </div>
            </td>
        </tr>`;
    });
    if(tbody.innerHTML !== html) tbody.innerHTML = html;
}

function renderUsers() {
    const tbody = document.getElementById('usersList');
    const q = document.getElementById('searchUser').value.toLowerCase();
    
    let filtered = allUsersCache.filter(u => u.username.toLowerCase().includes(q));
    
    const totalPages = Math.ceil(filtered.length / usersPerPage) || 1;
    if (currentPage > totalPages) currentPage = totalPages;
    
    const start = (currentPage - 1) * usersPerPage;
    const paginated = filtered.slice(start, start + usersPerPage);
    
    let html = '';
    paginated.forEach(u => {
        let actions = '';
        if (!u.is_admin) {
            let banBtn = u.status_html.includes('مسدود') 
                ? `<button onclick="confirmAction('unban_user', ${u.id}, 'آیا از رفع مسدودیت کاربر ${u.username} اطمینان دارید؟')" class="btn btn-primary btn-sm" style="margin-left:5px">رفع بن</button>`
                : `<button onclick="openBanModal(${u.id}, '${u.username}')" class="btn btn-danger btn-sm" style="margin-left:5px">مسدود</button>`;
            
            actions = `${banBtn}
            <button onclick="viewUserMsgs(${u.id}, '${u.username}')" class="btn btn-primary btn-sm" style="margin-left:5px; background:linear-gradient(135deg, #3b82f6, #60a5fa)">👁</button>
            <button onclick="confirmAction('delete_user_msgs', ${u.id}, 'تمام پیام‌های کاربر ${u.username} حذف شود؟')" class="btn btn-ghost btn-sm" style="color:#ef4444; border-color:#ef4444">پاکسازی</button>`;
        }

        const onlineBadge = u.is_online_bool 
            ? '<span class="status-dot online"></span> آنلاین' 
            : '<span class="status-dot offline"></span> آفلاین';

        html += `<tr id="user-${u.id}">
            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="font-size:1.5em; background:rgba(255,255,255,0.1); width:40px; height:40px; display:flex; justify-content:center; align-items:center; border-radius:12px;">${u.sticker}</span>
                    <div style="display:flex; flex-direction:column; align-items:flex-start">
                        <span>${u.username}</span>
                        ${u.is_admin ? '<span style="font-size:0.7em; opacity:0.7; color:#818cf8">مدیر کل</span>' : ''}
                    </div>
                </div>
            </td>
            <td>${onlineBadge} <div style="font-size:0.75em; opacity:0.5; margin-top:4px">${u.time_ago}</div></td>
            <td class="mono" style="direction:ltr">${u.ip_address || '-'}</td>
            <td>${u.status_html}</td>
            <td><div style="display:flex; justify-content:flex-end;">${actions}</div></td>
        </tr>`;
    });
    
    tbody.innerHTML = html;
    
    let pagHtml = '';
    for(let i=1; i<=totalPages; i++) {
        pagHtml += `<button onclick="changePage(${i})" class="btn ${i === currentPage ? 'btn-primary' : 'btn-ghost'} btn-sm" style="margin:2px; min-width:35px">${i}</button>`;
    }
    document.getElementById('paginationControls').innerHTML = pagHtml;
}

function changePage(p) {
    currentPage = p;
    renderUsers();
}

function filterUsers() {
    currentPage = 1; 
    renderUsers();
}

setInterval(refreshData, 3000);
refreshData();

</script>
</body>
</html>
<?php
function time_elapsed_string($datetime) {
    if (empty($datetime) || $datetime == '0000-00-00 00:00:00') return 'نامشخص';
    try {
        $ts = strtotime($datetime);
        $diff = time() - $ts;
    } catch (Exception $e) { return '-'; }

    if ($diff < 60) return 'همین الان';
    if ($diff < 3600) return floor($diff / 60) . ' دقیقه پیش';
    if ($diff < 86400) return floor($diff / 3600) . ' ساعت پیش';
    return floor($diff / 86400) . ' روز پیش';
}
?>