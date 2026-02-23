<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'عدم دسترسی']);
    exit;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT is_banned_until, muted_until, last_notif_id, notifications_enabled FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch();
    
    if (!$userData) {
        session_destroy();
        echo json_encode(['status' => 'error', 'message' => 'کاربر یافت نشد']);
        exit;
    }
    
    if ($userData['is_banned_until'] > time()) {
        $rem = $userData['is_banned_until'] - time();
        if ($rem > 31536000) {
             $msg = 'حساب شما دائماً مسدود شده است';
        } else {
             $min = ceil($rem / 60);
             $msg = "شما به مدت $min دقیقه مسدود شده‌اید و پس از آن آزاد می‌شوید";
        }
        echo json_encode(['status' => 'banned', 'message' => $msg]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            echo json_encode(['status' => 'error', 'message' => 'نشست نامعتبر']);
            exit;
        }

        $action = $_POST['action'];

        if (in_array($action, ['send', 'delete_msg', 'edit_msg', 'delete_all_msgs'])) {
            if (!isset($_POST['bw_nonce']) || !isset($_SESSION['bw_nonce']) || $_POST['bw_nonce'] !== $_SESSION['bw_nonce']) {
                echo json_encode(['status' => 'error', 'message' => 'خطای امنیتی - لطفا صفحه را رفرش کنید']);
                exit;
            }
        }

        if ($action === 'create_private_room') {
            $roomName = trim($_POST['room_name']);
            
            if (empty($roomName) || mb_strlen($roomName, 'UTF-8') < 4 || mb_strlen($roomName, 'UTF-8') > 10) {
                echo json_encode(['status' => 'error', 'message' => 'نام اتاق باید بین ۴ تا ۱۰ کاراکتر باشد']);
                exit;
            }

            if (!preg_match('/^[a-zA-Z0-9\x{0600}-\x{06FF}\s\x{200C}]+$/u', $roomName)) {
                echo json_encode(['status' => 'error', 'message' => 'نام اتاق فقط می‌تواند شامل حروف فارسی، انگلیسی و اعداد باشد']);
                exit;
            }

            $roomName = htmlspecialchars($roomName, ENT_QUOTES, 'UTF-8');

            $stmtChk = $pdo->prepare("SELECT id FROM rooms WHERE created_by = ?");
            $stmtChk->execute([$userId]);
            if ($stmtChk->rowCount() > 0) {
                echo json_encode(['status' => 'error', 'message' => 'شما قبلاً یک اتاق ساخته‌اید']);
                exit;
            }

            $stmtName = $pdo->prepare("SELECT id FROM rooms WHERE name = ?");
            $stmtName->execute([$roomName]);
            if ($stmtName->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'این نام اتاق تکراری است']);
                exit;
            }

            $inviteCode = substr(bin2hex(random_bytes(4)), 0, 6); 
            $stmtInsert = $pdo->prepare("INSERT INTO rooms (name, type, created_by, invite_code) VALUES (?, 'private', ?, ?)");
            $stmtInsert->execute([$roomName, $userId, $inviteCode]);
            
            $stmtSelfInvite = $pdo->prepare("INSERT INTO room_invites (user_id, room_name, invited_by) VALUES (?, ?, ?)");
            $stmtSelfInvite->execute([$userId, $roomName, $userId]);

            echo json_encode(['status' => 'success', 'message' => 'اتاق خصوصی با موفقیت ساخته شد', 'invite_code' => $inviteCode]);
            exit;
        }

        if ($action === 'search_invite_code') {
            $code = trim($_POST['code']);
            $stmt = $pdo->prepare("SELECT id, name FROM rooms WHERE invite_code = ?");
            $stmt->execute([$code]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($room) {
                echo json_encode(['status' => 'success', 'room' => $room]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'اتاقی با این کد یافت نشد']);
            }
            exit;
        }

        if ($action === 'join_via_code') {
            $code = trim($_POST['code']);
            $stmt = $pdo->prepare("SELECT name, id FROM rooms WHERE invite_code = ?");
            $stmt->execute([$code]);
            $roomInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$roomInfo) {
                echo json_encode(['status' => 'error', 'message' => 'کد نامعتبر است']);
                exit;
            }
            $roomName = $roomInfo['name'];
            $roomId = $roomInfo['id'];

            $stmtChk = $pdo->prepare("SELECT id FROM room_invites WHERE user_id = ? AND room_name = ?");
            $stmtChk->execute([$userId, $roomName]);
            if ($stmtChk->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'شما قبلاً عضو این اتاق هستید']);
                exit;
            }

            $stmtJoin = $pdo->prepare("INSERT INTO room_invites (user_id, room_name, invited_by) VALUES (?, ?, ?)");
            $stmtJoin->execute([$userId, $roomName, $userId]);
            
            $sysMsg = "*** کاربر $username به اتاق پیوست ***";
            $encMsg = encrypt_data($sysMsg);
            $pdo->prepare("INSERT INTO messages (sender_id, room_name, message, msg_type) VALUES (?, ?, ?, 'text')")
                ->execute([$userId, $roomName, $encMsg]);
            
            echo json_encode(['status' => 'success', 'message' => 'شما با موفقیت به اتاق پیوستید', 'room_name' => $roomName, 'room_id' => $roomId]);
            exit;
        }

        if ($action === 'toggle_notifications') {
            $state = (int)$_POST['state'];
            $pdo->prepare("UPDATE users SET notifications_enabled = ? WHERE id = ?")->execute([$state, $userId]);
            echo json_encode(['status' => 'success']);
            exit;
        }

        if ($action === 'set_offline') {
            $pdo->prepare("UPDATE users SET is_online = 0 WHERE id = ?")->execute([$userId]);
            exit;
        }

        if ($action === 'update_typing') {
            $context = isset($_POST['room']) && $_POST['room'] !== 'null' ? $_POST['room'] : 'private_' . $_POST['target_user'];
            $pdo->prepare("UPDATE users SET typing_context = ?, typing_time = ? WHERE id = ?")->execute([$context, time(), $userId]);
            echo json_encode(['status' => 'success']);
            exit;
        }

        if ($action === 'delete_all_msgs') {
            try {
                $stmtFiles = $pdo->prepare("SELECT file_path FROM messages WHERE sender_id = ? AND file_path IS NOT NULL");
                $stmtFiles->execute([$userId]);
                $filesToDelete = $stmtFiles->fetchAll(PDO::FETCH_COLUMN);

                $pdo->beginTransaction();
                
                $pdo->prepare("UPDATE messages SET reply_to_id = NULL WHERE reply_to_id IN (SELECT id FROM (SELECT id FROM messages WHERE sender_id = ?) AS sub)")->execute([$userId]);
                
                $stmtDel = $pdo->prepare("DELETE FROM messages WHERE sender_id = ?");
                $stmtDel->execute([$userId]);
                $pdo->commit();

                foreach($filesToDelete as $path) {
                    if(file_exists($path)) {
                        @unlink($path);
                    }
                }
                echo json_encode(['status' => 'success', 'message' => 'تمام پیام‌های شما با موفقیت حذف شدند']);
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'خطا در حذف: ' . $e->getMessage()]);
            }
            exit;
        }

        if ($action === 'delete_msg') {
            $msgId = (int)$_POST['id'];
            $stmt = $pdo->prepare("SELECT sender_id, file_path FROM messages WHERE id = ?");
            $stmt->execute([$msgId]);
            $msg = $stmt->fetch();
            
            if ($msg && ($msg['sender_id'] == $userId || $username === $admin_username)) {
                $pdo->prepare("UPDATE messages SET reply_to_id = NULL WHERE reply_to_id = ?")->execute([$msgId]);
                $pdo->prepare("DELETE FROM messages WHERE id = ?")->execute([$msgId]);
                if ($msg['file_path'] && file_exists($msg['file_path'])) {
                    @unlink($msg['file_path']);
                }
                echo json_encode(['status' => 'success', 'message' => 'پیام با موفقیت حذف شد']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'عدم دسترسی']);
            }
            exit;
        }

        if ($action === 'edit_msg') {
            $msgId = (int)$_POST['id'];
            $newText = trim($_POST['text']);
            if (mb_strlen($newText) > 1000) { 
                echo json_encode(['status' => 'error', 'message' => 'متن طولانی است']); 
                exit; 
            }
            $stmt = $pdo->prepare("SELECT sender_id, msg_type FROM messages WHERE id = ?");
            $stmt->execute([$msgId]);
            $msg = $stmt->fetch();
            if ($msg && $msg['sender_id'] == $userId && $msg['msg_type'] === 'text') {
                $encText = encrypt_data($newText);
                $pdo->prepare("UPDATE messages SET message = ?, is_edited = 1 WHERE id = ?")->execute([$encText, $msgId]);
                echo json_encode(['status' => 'success', 'message' => 'پیام با موفقیت ویرایش شد']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'غیرقابل ویرایش']);
            }
            exit;
        }

        if ($action === 'send') {
            if ($userData['muted_until'] > time()) {
                echo json_encode(['status' => 'error', 'message' => 'شما به دلیل اسپم موقتاً محدود شده‌اید']);
                exit;
            }

            $settings = [];
            $stmtSet = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
            while ($row = $stmtSet->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            $isUploadLocked = isset($settings['lock_upload']) && $settings['lock_upload'] === '1';
            $isVoiceLocked = isset($settings['lock_voice']) && $settings['lock_voice'] === '1';

            if (isset($_FILES['file']) && $isUploadLocked) {
                echo json_encode(['status' => 'error', 'message' => 'ارسال فایل توسط مدیریت مسدود شده است']);
                exit;
            }

            if (isset($_POST['is_voice']) && $_POST['is_voice'] === 'true' && $isVoiceLocked) {
                echo json_encode(['status' => 'error', 'message' => 'ارسال ویس توسط مدیریت مسدود شده است']);
                exit;
            }

            $now = time();
            if (!isset($_SESSION['spam_check'])) $_SESSION['spam_check'] = [];
            $_SESSION['spam_check'][] = $now;
            $_SESSION['spam_check'] = array_filter($_SESSION['spam_check'], function($t) use ($now, $spam_limit_time) {
                return ($now - $t) < $spam_limit_time;
            });
            
            $rawRoom = isset($_POST['room']) ? $_POST['room'] : null;
            $roomId = ($rawRoom === 'null' || $rawRoom === '') ? null : htmlspecialchars($rawRoom);
            if ($roomId === 'public') $roomId = 'گفتگوی عمومی';

            if (count($_SESSION['spam_check']) > $spam_limit_count) {
                $muteUntil = $now + $ban_duration; 
                $pdo->prepare("UPDATE users SET muted_until = ? WHERE id = ?")->execute([$muteUntil, $userId]);
                
                $sysMsg = "*** کاربر $username به دلیل ارسال پیام‌های مکرر به مدت ۲ دقیقه سکوت شد ***";
                $encMsg = encrypt_data($sysMsg);
                $pdo->prepare("INSERT INTO messages (sender_id, room_name, message, msg_type) VALUES (?, ?, ?, 'text')")
                    ->execute([$userId, $roomId ?: 'گفتگوی عمومی', $encMsg]);

                echo json_encode(['status' => 'error', 'message' => 'شما به دلیل اسپم ۲ دقیقه محدود شدید']); 
                exit;
            }

            $msgType = 'text';
            $messageRaw = isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '';
            $filePath = null; $fileName = null; $fileToken = null;

            $receiverId = (isset($_POST['receiver_id']) && is_numeric($_POST['receiver_id']) && $_POST['receiver_id'] > 0) ? (int)$_POST['receiver_id'] : null;
            $replyToId = null;
            if (isset($_POST['reply_to']) && !empty($_POST['reply_to'])) {
                $replyToId = (int)$_POST['reply_to'];
            }

            if ($roomId === 'گفتگوی عمومی') $receiverId = null;

            if (!$receiverId && $roomId && $roomId !== 'گفتگوی عمومی') {
                $stmtCheck = $pdo->prepare("SELECT type FROM rooms WHERE name = ?");
                $stmtCheck->execute([$roomId]);
                $rType = $stmtCheck->fetchColumn();
                if ($rType === 'private' && $username !== $admin_username) {
                    $stmtPerm = $pdo->prepare("SELECT id FROM room_invites WHERE user_id = ? AND room_name = ?");
                    $stmtPerm->execute([$userId, $roomId]);
                    if (!$stmtPerm->fetch()) {
                        echo json_encode(['status' => 'access_denied', 'message' => 'شما به این اتاق دعوت نشده‌اید']); 
                        exit;
                    }
                }
            }

            if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
                if ($_FILES['file']['size'] > $file_size_limit) { 
                    echo json_encode(['status' => 'error', 'message' => 'حجم فایل زیاد است (حداکثر ۵ مگابایت)']); 
                    exit; 
                }
                $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
                $denied = ['php', 'php3', 'php4', 'php5', 'phtml', 'exe', 'sh', 'bat', 'cmd', 'js', 'html', 'htm', 'pl', 'py', 'htaccess'];
                if (in_array($ext, $denied)) { 
                    echo json_encode(['status' => 'error', 'message' => 'فرمت فایل مجاز نیست']); 
                    exit; 
                }
                
                $newFileName = bin2hex(random_bytes(16)) . '.' . $ext;
                $uploadPath = 'uploads/' . $newFileName;
                $fileContent = file_get_contents($_FILES['file']['tmp_name']);
                $encryptedContent = encrypt_data($fileContent);
                if (file_put_contents($uploadPath, $encryptedContent)) {
                    $filePath = $uploadPath;
                    $fileName = $_FILES['file']['name'];
                    $fileToken = bin2hex(random_bytes(16));
                    if (isset($_POST['is_voice']) && $_POST['is_voice'] === 'true') {
                        $msgType = 'voice';
                    } else {
                        $msgType = (in_array($ext, ['mp3', 'wav', 'ogg', 'webm', 'm4a'])) ? 'voice' : 'file';
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'خطا در ذخیره فایل']); exit;
                }
            }

            if (empty($messageRaw) && empty($filePath)) { 
                echo json_encode(['status' => 'error', 'message' => 'پیام خالی است']); exit; 
            }

            $messageEncrypted = $messageRaw ? encrypt_data($messageRaw) : '';
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, room_name, message, file_path, file_name, file_token, msg_type, reply_to_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $receiverId, $roomId, $messageEncrypted, $filePath, $fileName, $fileToken, $msgType, $replyToId]);
            $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?")->execute([$userId]);
            echo json_encode(['status' => 'success', 'new_msg_id' => $pdo->lastInsertId()]); 
            exit;
        }

        if ($action === 'fetch_history') {
            $roomId = isset($_POST['room']) ? $_POST['room'] : null;
            $targetUserId = isset($_POST['target_user']) ? (int)$_POST['target_user'] : null;
            $beforeId = (int)$_POST['before_id'];
            
            $sql = "SELECT m.*, u.username as sender_name, u.sticker as sender_sticker,
                    rm.message as reply_message, rm.file_name as reply_file_name, rm.msg_type as reply_msg_type, ru.username as reply_sender
                    FROM messages m 
                    JOIN users u ON m.sender_id = u.id 
                    LEFT JOIN messages rm ON m.reply_to_id = rm.id
                    LEFT JOIN users ru ON rm.sender_id = ru.id
                    WHERE m.id < ? AND ";
            
            $params = [$beforeId];
            
            if ($targetUserId) {
                $sql .= "((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)) AND m.room_name IS NULL";
                array_push($params, $userId, $targetUserId, $targetUserId, $userId);
            } else {
                $safeRoom = ($roomId && $roomId !== 'null') ? $roomId : 'گفتگوی عمومی';
                $sql .= "m.room_name = ? AND m.receiver_id IS NULL";
                $params[] = $safeRoom;
            }
            
            $sql .= " ORDER BY m.id DESC LIMIT 20";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rawMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $messages = [];
            foreach(array_reverse($rawMessages) as $m) {
                try { 
                    if(!empty($m['message'])) $m['message'] = decrypt_data($m['message']); 
                    else $m['message'] = '';
                } catch (Exception $e) { $m['message'] = 'Error'; }
                try { 
                    if(!empty($m['reply_message'])) $m['reply_message'] = decrypt_data($m['reply_message']); 
                } catch (Exception $e) { $m['reply_message'] = '...'; }
                unset($m['file_path']);
                $messages[] = $m;
            }
            echo json_encode(['status' => 'success', 'messages' => $messages]);
            exit;
        }

        if ($action === 'fetch') {
            $pdo->prepare("UPDATE users SET is_online = 1, last_activity = NOW() WHERE id = ?")->execute([$userId]);

            $roomId = isset($_POST['room']) ? $_POST['room'] : null;
            $targetUserId = isset($_POST['target_user']) && is_numeric($_POST['target_user']) ? (int)$_POST['target_user'] : null;
            $isAdmin = ($username === $admin_username);

            if (!$targetUserId && $roomId && $roomId !== 'گفتگوی عمومی' && $roomId !== 'public' && $roomId !== 'null') {
                $stmtCheck = $pdo->prepare("SELECT type FROM rooms WHERE name = ?");
                $stmtCheck->execute([$roomId]);
                $rType = $stmtCheck->fetchColumn();
                if ($rType === 'private' && !$isAdmin) {
                    $stmtPerm = $pdo->prepare("SELECT id FROM room_invites WHERE user_id = ? AND room_name = ?");
                    $stmtPerm->execute([$userId, $roomId]);
                    if (!$stmtPerm->fetch()) {
                        echo json_encode(['status' => 'access_denied']);
                        exit;
                    }
                }
            }

            if ($targetUserId) {
                $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0")->execute([$targetUserId, $userId]);
            }

            $sql = "SELECT m.*, u.username as sender_name, u.sticker as sender_sticker,
                    rm.message as reply_message, rm.file_name as reply_file_name, rm.msg_type as reply_msg_type, ru.username as reply_sender
                    FROM messages m 
                    JOIN users u ON m.sender_id = u.id 
                    LEFT JOIN messages rm ON m.reply_to_id = rm.id
                    LEFT JOIN users ru ON rm.sender_id = ru.id
                    WHERE ";
            
            $params = [];
            
            if ($targetUserId) {
                $sql .= "((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)) AND m.room_name IS NULL";
                $params = [$userId, $targetUserId, $targetUserId, $userId];
            } else {
                $safeRoom = ($roomId && $roomId !== 'null') ? $roomId : 'گفتگوی عمومی';
                $sql .= "m.room_name = ? AND m.receiver_id IS NULL";
                $params = [$safeRoom];
            }
            
            $sql .= " ORDER BY m.created_at DESC LIMIT 20";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rawMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $messages = [];
            foreach(array_reverse($rawMessages) as $m) {
                try { 
                    if(!empty($m['message'])) $m['message'] = decrypt_data($m['message']); 
                    else $m['message'] = '';
                } catch (Exception $e) { $m['message'] = 'Error'; }
                try { 
                    if(!empty($m['reply_message'])) $m['reply_message'] = decrypt_data($m['reply_message']); 
                } catch (Exception $e) { $m['reply_message'] = '...'; }
                unset($m['file_path']);
                $messages[] = $m;
            }
            
            $lastNotifId = $userData['last_notif_id'] ?? 0;
            $newNotifs = [];
            
            $isAdminInt = ($username === $admin_username) ? 1 : 0;
            
            $sqlNotif = "SELECT m.*, u.username as sender_name, u.sticker as sender_sticker 
                         FROM messages m 
                         JOIN users u ON m.sender_id = u.id 
                         LEFT JOIN rooms r ON m.room_name = r.name
                         WHERE m.id > ? 
                         AND m.sender_id != ? 
                         AND (
                             m.receiver_id = ? 
                             OR (m.receiver_id IS NULL AND (
                                 m.room_name = 'گفتگوی عمومی' 
                                 OR m.room_name IS NULL
                                 OR r.type = 'public' 
                                 OR r.created_by = ? 
                                 OR EXISTS (SELECT 1 FROM room_invites ri WHERE ri.room_name = m.room_name AND ri.user_id = ?)
                                 OR ? = 1
                             ))
                         )";
            $stmtNotif = $pdo->prepare($sqlNotif);
            $stmtNotif->execute([$lastNotifId, $userId, $userId, $userId, $userId, $isAdminInt]);
            $rawNotifs = $stmtNotif->fetchAll(PDO::FETCH_ASSOC);

            $maxId = $lastNotifId;
            foreach($rawNotifs as $nm) {
                if ($nm['id'] > $maxId) $maxId = $nm['id'];
                $txt = $nm['msg_type'] === 'text' ? decrypt_data($nm['message']) : ($nm['msg_type'] === 'voice' ? 'پیام صوتی' : 'فایل');
                $newNotifs[] = [
                    'sender_id' => $nm['sender_id'],
                    'name' => $nm['sender_name'],
                    'sticker' => $nm['sender_sticker'],
                    'txt' => $txt,
                    'msg_id' => $nm['id'],
                    'room_name' => $nm['room_name'] 
                ];
            }

            if ($maxId > $lastNotifId) {
                $pdo->prepare("UPDATE users SET last_notif_id = ? WHERE id = ?")->execute([$maxId, $userId]);
            }
            
            $typingContext = $targetUserId ? 'private_'.$userId : ($roomId ?: 'گفتگوی عمومی');
            $searchContext = $targetUserId ? 'private_'.$targetUserId : ($roomId ?: 'گفتگوی عمومی');

            $stmtTyping = $pdo->prepare("SELECT username FROM users WHERE typing_context = ? AND typing_time > ? AND id != ?");
            $stmtTyping->execute([$searchContext, time() - 3, $userId]);
            $typingUsers = $stmtTyping->fetchAll(PDO::FETCH_COLUMN);

            $onlineQuery = "SELECT id, username, sticker, last_activity, is_banned_until, is_online FROM users WHERE last_activity > DATE_SUB(NOW(), INTERVAL 10 MINUTE) AND is_banned_until < UNIX_TIMESTAMP() ORDER BY last_activity DESC";
            $onlineUsers = $pdo->query($onlineQuery)->fetchAll(PDO::FETCH_ASSOC);

            $unreadStmt = $pdo->prepare("SELECT sender_id, COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0 GROUP BY sender_id");
            $unreadStmt->execute([$userId]);
            $unreadCounts = $unreadStmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $roomsStmt = $pdo->query("SELECT r.*, u.username as creator FROM rooms r LEFT JOIN users u ON r.created_by = u.id ORDER BY r.id DESC");
            $allRooms = $roomsStmt->fetchAll(PDO::FETCH_ASSOC);
            $stmtMyInvites = $pdo->prepare("SELECT room_name FROM room_invites WHERE user_id = ?");
            $stmtMyInvites->execute([$userId]);
            $myInvites = $stmtMyInvites->fetchAll(PDO::FETCH_COLUMN);
            $filteredRooms = [];
            foreach($allRooms as $room) {
                if ($room['type'] === 'public' || $isAdmin || in_array($room['name'], $myInvites)) {
                    if (!$room['creator']) $room['creator'] = 'سیستم';
                    $filteredRooms[] = $room;
                }
            }

            $settings = [];
            $stmtSet = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
            while ($row = $stmtSet->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }

            $stmtAlert = $pdo->query("SELECT id, message FROM global_alerts ORDER BY id DESC LIMIT 1");
            $globalAlert = $stmtAlert->fetch(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success', 
                'messages' => $messages, 
                'users' => $onlineUsers, 
                'rooms' => $filteredRooms,
                'my_invites' => $myInvites,
                'unread' => $unreadCounts,
                'notifications' => $newNotifs,
                'typing_users' => $typingUsers,
                'settings' => $settings,
                'global_alert' => $globalAlert,
                'current_user_id' => $userId,
                'user_settings' => ['notifications' => $userData['notifications_enabled']],
                'is_admin' => $isAdmin,
                'bw_nonce' => $_SESSION['bw_nonce'] ?? ''
            ]);
            exit;
        }
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطای سرور: ' . $e->getMessage()]);
    exit;
}
?>