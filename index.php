<?php
require_once 'config.php';

try {
    $lStmt = $pdo->prepare("SELECT value FROM settings WHERE name = 'site_lock'");
    $lStmt->execute();
    if ($lStmt->fetchColumn() == '1') {
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" type="image/png" href="<?php echo $favicon_path; ?>">
    <title>سایت در دسترس نیست</title>
    <style>
        @font-face { font-family: 'AppFont'; src: url('fonts/<?php echo $font_text ?? 'font.ttf'; ?>.ttf') format('truetype'); }
        * { box-sizing: border-box; font-family: 'AppFont', sans-serif; }
        body { margin: 0; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fff; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; overflow: hidden; }
        .lock-container { width: 100%; max-width: 450px; position: relative; z-index: 10; padding: 10px; }
        .lock-card { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); padding: 50px 30px; border-radius: 35px; border: 1px solid rgba(255, 255, 255, 0.1); text-align: center; box-shadow: 0 30px 80px -10px rgba(0, 0, 0, 0.6); position: relative; overflow: hidden; }
        .lock-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(90deg, #ef4444, #f87171, #ef4444); background-size: 200% 100%; animation: gradientMove 3s linear infinite; }
        .lock-icon-box { width: 90px; height: 90px; background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05)); border-radius: 50%; display: flex; justify-content: center; align-items: center; margin: 0 auto 30px; border: 1px solid rgba(239, 68, 68, 0.3); animation: pulse 3s infinite; }
        .icon { font-size: 40px; animation: float 4s ease-in-out infinite; filter: drop-shadow(0 0 10px rgba(239, 68, 68, 0.5)); }
        .title { font-size: 26px; font-weight: bold; margin-bottom: 20px; color: #fff; letter-spacing: -0.5px; text-shadow: 0 2px 10px rgba(0,0,0,0.3); }
        .desc { font-size: 15px; color: #cbd5e1; line-height: 1.8; margin-bottom: 35px; padding: 0 15px; }
        .btn-check { width: 100%; background: linear-gradient(135deg, #334155, #1e293b); color: #fff; border: 1px solid rgba(255,255,255,0.15); padding: 16px; border-radius: 18px; cursor: pointer; font-size: 15px; transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); font-weight: bold; box-shadow: 0 8px 25px rgba(0,0,0,0.2); }
        .btn-check:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(0,0,0,0.3); border-color: rgba(255,255,255,0.3); background: linear-gradient(135deg, #475569, #334155); }
        .btn-check:active { transform: translateY(0); }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); } 70% { box-shadow: 0 0 0 20px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
        @keyframes gradientMove { 0% { background-position: 0% 50%; } 100% { background-position: 100% 50%; } }
        .glow { position: absolute; width: 200px; height: 200px; background: #ef4444; filter: blur(120px); opacity: 0.12; top: -80px; left: -80px; border-radius: 50%; z-index: -1; }
    </style>
</head>
<body>
    <div class="lock-container">
        <div class="lock-card">
            <div class="glow"></div>
            <div class="lock-icon-box">
                <div class="icon">🔒</div>
            </div>
            <div class="title">دسترسی موقتاً محدود است</div>
            <div class="desc">
                کاربر گرامی، سیستم جهت ارتقاء زیرساخت و به‌روزرسانی فنی موقتاً از دسترس خارج شده است.
                <br>از شکیبایی شما سپاسگزاریم.
            </div>
            <button class="btn-check" onclick="location.reload()">بررسی وضعیت اتصال</button>
        </div>
    </div>
</body>
</html>
<?php
        exit;
    }
} catch (Exception $e) {}

$isReportLocked = false;
try {
    $stmtRep = $pdo->prepare("SELECT value FROM settings WHERE name = 'report_lock'");
    $stmtRep->execute();
    $isReportLocked = ($stmtRep->fetchColumn() == '1');
} catch (Exception $e) {}

if (empty($_SESSION['logout_nonce'])) {
    $_SESSION['logout_nonce'] = bin2hex(random_bytes(16));
}

$secCode = '';
if (isset($_SESSION['user_id'])) {
    try {
        $stmtSec = $pdo->prepare("SELECT security_code FROM users WHERE id = ?");
        $stmtSec->execute([$_SESSION['user_id']]);
        $secCode = $stmtSec->fetchColumn();
    } catch (Exception $e) {}
}

if (!isset($_SESSION['user_id'])) {
    $n1 = rand(10, 50);
    $n2 = rand(1, 9);
    $_SESSION['captcha_result'] = $n1 + $n2;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, interactive-widget=resizes-content">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <meta name="theme-color" content="#1e293b">
    <link rel="icon" type="image/png" href="<?php echo $favicon_path; ?>">
    <title>ورود به چت</title>
    <style>
        @font-face { font-family: 'AppFont'; src: url('fonts/<?php echo $font_text; ?>.ttf') format('truetype'); font-weight: normal; }
        @font-face { font-family: 'AppBold'; src: url('fonts/<?php echo $font_heading; ?>.ttf') format('truetype'); font-weight: bold; }
        * { font-family: 'AppFont', sans-serif; box-sizing: border-box; outline: none; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; background-color: #0f172a; color: #fff; display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 100vh; min-height: 100dvh; overflow: hidden; background-image: radial-gradient(circle at top right, #1e293b, #0f172a); -webkit-touch-callout: none; }
        .login-card { background: rgba(30, 41, 59, 0.85); backdrop-filter: blur(20px); padding: 40px 30px; border-radius: 28px; width: 90%; max-width: 400px; text-align: center; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 25px 60px rgba(0,0,0,0.6); margin-bottom: 20px; transition: 0.3s; }
        .brand-title { font-family: 'AppBold'; font-size: 28px; margin: 0 0 5px; color: #fff; }
        .brand-subtitle { font-size: 13px; color: #94a3b8; margin-bottom: 30px; }
        input { width: 100%; height: 50px; background: rgba(15, 23, 42, 0.7); border: 1px solid #334155; color: #fff; border-radius: 14px; text-align: center; font-size: 16px; margin-bottom: 15px; transition: 0.3s; }
        input:focus { border-color: #6366f1; background: rgba(15, 23, 42, 0.95); }
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }
        
        .input-error { border-color: #ef4444 !important; background: rgba(239, 68, 68, 0.1) !important; animation: shake 0.4s; }
        .error-msg-box { color: #ef4444; font-size: 12px; margin-bottom: 10px; display: none; width: 100%; word-break: break-word; overflow-wrap: break-word; line-height: 1.4; }
        @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }

        .captcha-row { display: flex; gap: 10px; margin-bottom: 15px; }
        .captcha-text { flex: 1; height: 50px; background: rgba(99, 102, 241, 0.1); border: 1px dashed rgba(99, 102, 241, 0.4); border-radius: 14px; display: flex; justify-content: center; align-items: center; font-weight: bold; color: #818cf8; font-size: 20px; font-family: 'AppBold'; overflow: hidden; white-space: nowrap; user-select: none; }
        .refresh-btn { width: 50px; height: 50px; background: rgba(15, 23, 42, 0.7); border: 1px solid #334155; border-radius: 14px; color: #94a3b8; cursor: pointer; display: flex; justify-content: center; align-items: center; font-size: 22px; transition: 0.3s; }
        .refresh-btn.rotating { animation: spin-icon 0.6s ease-in-out; background: rgba(99, 102, 241, 0.3); color: #fff; border-color: #818cf8; }
        button { width: 100%; height: 50px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; border: none; border-radius: 14px; font-weight: bold; font-family: 'AppBold'; font-size: 16px; cursor: pointer; margin-top: 5px; box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4); transition: 0.3s; }
        button:hover { transform: translateY(-2px); box-shadow: 0 15px 35px -5px rgba(99, 102, 241, 0.5); }
        .btn-secondary { background: linear-gradient(135deg, #334155, #1e293b); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.3); margin-bottom: 10px; border: 1px solid rgba(255,255,255,0.1); }
        .btn-secondary:hover { background: linear-gradient(135deg, #475569, #334155); border-color: rgba(255,255,255,0.2); }
        
        #toast { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%) translateY(50px); background: rgba(30, 41, 59, 0.95); backdrop-filter: blur(10px); border: 1px solid rgba(239, 68, 68, 0.5); color: white; padding: 15px 25px; border-radius: 20px; font-size: 14px; opacity: 0; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); pointer-events: none; z-index: 999; max-width: 90%; width: max-content; text-align: center; line-height: 1.6; box-shadow: 0 10px 40px rgba(0,0,0,0.5); }
        #toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
        
        #securityCodeInput::placeholder { font-family: 'AppFont'; opacity: 0.7; letter-spacing: 0px; font-weight: normal; }

        .hidden { display: none !important; }
        #loadingOverlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.95); z-index: 2000; display: none; flex-direction: column; justify-content: center; align-items: center; }
        .spinner { width: 50px; height: 50px; border: 5px solid rgba(255,255,255,0.1); border-top-color: #6366f1; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 20px; }
        .login-footer { margin-top: 20px; display: flex; flex-direction: column; align-items: center; gap: 8px; font-size: 12px; color: #64748b; }
        .terms-link { color: #818cf8; cursor: pointer; text-decoration: none; border-bottom: 1px dashed #818cf8; padding-bottom: 2px; transition: 0.2s; }
        .terms-link:hover { color: #a5b4fc; border-bottom-color: #a5b4fc; }
        .dev-credit { font-family: 'monospace', sans-serif; opacity: 0.7; letter-spacing: 0.5px; }
        .version-tag { font-size: 10px; color: #64748b; margin-top: 5px; opacity: 0.6; font-family: sans-serif; letter-spacing: 1px; }
        .terms-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 2500; display: flex; justify-content: center; align-items: center; backdrop-filter: blur(5px); opacity: 0; visibility: hidden; transition: 0.3s; }
        .terms-modal-overlay.active { opacity: 1; visibility: visible; }
        .terms-modal { background: #1e293b; width: 90%; max-width: 500px; max-height: 80vh; border-radius: 24px; border: 1px solid #334155; display: flex; flex-direction: column; position: relative; box-shadow: 0 25px 50px rgba(0,0,0,0.7); transform: scale(0.95); transition: 0.3s; }
        .terms-modal-overlay.active .terms-modal { transform: scale(1); }
        .terms-header { padding: 20px; border-bottom: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; }
        .terms-title { font-family: 'AppBold'; font-size: 18px; color: #fff; }
        .close-terms { cursor: pointer; color: #94a3b8; font-size: 24px; line-height: 1; }
        .terms-body { padding: 20px; overflow-y: auto; color: #cbd5e1; font-size: 14px; line-height: 1.8; text-align: justify; }
        
        .ban-modal-content { background: rgba(50, 0, 0, 0.8); backdrop-filter: blur(20px); border: 1px solid rgba(239, 68, 68, 0.4); background-image: linear-gradient(145deg, rgba(69, 10, 10, 0.9), rgba(26, 5, 5, 0.95)); border-radius: 30px; padding: 40px 30px; text-align: center; width: 100%; max-width: 450px; box-shadow: 0 25px 60px rgba(0,0,0,0.8); position: relative; overflow: hidden; margin: 0 auto; }
        .ban-modal-content::after { content: ''; position: absolute; inset: 0; border-radius: 30px; padding: 2px; background: linear-gradient(45deg, transparent, rgba(239, 68, 68, 0.3), transparent); -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0); -webkit-mask-composite: xor; mask-composite: exclude; pointer-events: none; }
        .ban-icon { font-size: 65px; margin-bottom: 20px; display: inline-block; filter: drop-shadow(0 0 25px rgba(239, 68, 68, 0.5)); animation: pulseBan 2s infinite; }
        .ban-title { color: #ef4444; font-size: 24px; margin-bottom: 15px; font-family: 'AppBold'; text-shadow: 0 0 20px rgba(239, 68, 68, 0.4); }
        .ban-message { color: #cbd5e1; font-size: 15px; line-height: 1.8; margin-bottom: 30px; background: rgba(0,0,0,0.25); padding: 20px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.05); }
        .ban-btn { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 12px 30px; border-radius: 12px; cursor: pointer; transition: 0.3s; font-family: 'AppBold'; font-size: 14px; }
        .ban-btn:hover { background: rgba(255,255,255,0.2); transform: translateY(-2px); }
        @keyframes pulseBan { 0% { transform: scale(1); } 50% { transform: scale(1.05); filter: drop-shadow(0 0 35px rgba(239, 68, 68, 0.7)); } 100% { transform: scale(1); } }
        
        @keyframes spin { 100% { transform: rotate(360deg); } }
        @keyframes spin-icon { 100% { transform: rotate(360deg); } }

        .intro-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 3000; display: flex; justify-content: center; align-items: center; backdrop-filter: blur(8px); opacity: 0; visibility: hidden; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .intro-overlay.active { opacity: 1; visibility: visible; }
        .intro-card { background: #1e293b; width: 90%; max-width: 420px; padding: 35px 25px; border-radius: 28px; border: 1px solid #334155; text-align: center; position: relative; box-shadow: 0 25px 50px rgba(0,0,0,0.8); transform: scale(0.9) translateY(20px); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); overflow: hidden; }
        .intro-overlay.active .intro-card { transform: scale(1) translateY(0); }
        .intro-close { position: absolute; top: 15px; left: 15px; width: 32px; height: 32px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; justify-content: center; align-items: center; cursor: pointer; color: #94a3b8; transition: 0.2s; font-size: 14px; }
        .intro-close:hover { background: #ef4444; color: #fff; transform: rotate(90deg); }
        .intro-icon { font-size: 55px; margin-bottom: 20px; animation: bounce 2s infinite; }
        .intro-title { font-family: 'AppBold'; font-size: 22px; color: #fff; margin-bottom: 15px; background: linear-gradient(135deg, #fff, #94a3b8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .intro-text { color: #cbd5e1; font-size: 14px; line-height: 1.8; margin-bottom: 25px; text-align: justify; padding: 0 10px; border-right: 2px solid #6366f1; padding-right: 15px; }
        .intro-btn { width: 100%; padding: 14px; border-radius: 14px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; border: none; font-family: 'AppBold'; font-size: 15px; cursor: pointer; transition: 0.3s; box-shadow: 0 10px 25px -5px rgba(99, 102, 241, 0.4); }
        .intro-btn:hover { transform: translateY(-3px); box-shadow: 0 15px 35px -5px rgba(99, 102, 241, 0.5); }
        @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
    </style>
</head>
<body>
    <div id="toast"></div>
    <div id="loadingOverlay"><div class="spinner"></div><div style="font-size:18px">لطفاً صبر کنید...</div></div>

<div id="introPopup" class="intro-overlay" onclick="closeIntroPopup()">
    <div class="intro-card" onclick="event.stopPropagation()">
        <span class="intro-close" onclick="closeIntroPopup()">✕</span>
        <div class="intro-icon">👋</div>
        <div class="intro-title">خوش اومدی به بلک‌واکر</div>
        <div class="intro-text">
            خوش اومدی به چت‌روم بلک‌واکر نسخه 1. این نسخه به‌صورت عمومی منتشر شده اما هنوز در حال توسعه‌ست و ممکنه با باگ یا محدودیت روبه‌رو بشی.
            <br><br>
            بعضی قابلیت‌ها مثل گزارش‌دهی، سیستم دعوت و ویس‌دهی (ویس‌کال) فعلاً فعال نیستن و به‌زودی اضافه می‌شن.
            <br><br>
            اگه با مشکلی برخورد کردی لطفاً به توسعه‌دهنده <b>«پویا فخام»</b> در تلگرام به آیدی 
            <span style="color:#818cf8;direction:ltr;display:inline-block">@PooyaFakham</span> 
            گزارش بده.
        </div>
        <button class="intro-btn" onclick="closeIntroPopup()">متوجه شدم</button>
    </div>
</div>

    <div class="login-card" id="mainLoginCard">
        <div class="brand-title">بلک واکر</div>
        <div class="brand-subtitle">پلتفرم گفتگوی امن</div>
        <form id="loginForm">
            <div id="step1">
                <input type="text" id="username" name="username" placeholder="نام کاربری (حتی فاصله‌دار)" required autocomplete="off">
                <div id="usernameError" class="error-msg-box"></div>
                <div class="captcha-row">
                    <div class="captcha-text" id="captchaBox"><?php echo $n1; ?> + <?php echo $n2; ?></div>
                    <div class="refresh-btn" onclick="refreshCaptcha()">↻</div>
                </div>
                <input type="number" name="captcha" id="captchaInput" placeholder="پاسخ امنیتی" required>
            </div>
            <div id="step2" class="hidden">
                <input type="password" id="password" name="password" placeholder="رمز عبور مدیریت" autocomplete="new-password">
            </div>
            
            <button type="button" id="btnWithCode" class="btn-secondary hidden" onclick="showLoginWithCode()">کد ورود امنیتی را دارم 🔒</button>
            <button type="submit" id="submitBtn">ورود</button>
        </form>
    </div>

    <div class="login-card hidden" id="loginWithCodeCard">
        <div class="brand-title" style="font-size:22px; margin-bottom:15px; color:#818cf8;">ورود با کد امنیتی</div>
        <form id="loginCodeForm">
            <input type="text" id="codeUsername" placeholder="نام کاربری شما" required autocomplete="off" readonly style="opacity:0.7; cursor:not-allowed;">
            <input type="text" id="securityCodeInput" placeholder="کد امنیتی ۸ رقمی" style="letter-spacing: 3px; font-weight:bold; font-family: monospace;" required autocomplete="off">
            
            <div class="captcha-row">
                <div class="captcha-text" id="captchaBoxCode">Loading...</div>
                <div class="refresh-btn" onclick="refreshCaptchaCode()">↻</div>
            </div>
            <input type="number" id="captchaInputCode" placeholder="پاسخ امنیتی" required>
            
            <button type="submit" style="background: linear-gradient(135deg, #10b981, #059669);">تایید و ورود</button>
            <button type="button" class="btn-secondary" style="margin-top:10px;" onclick="hideLoginWithCode()">بازگشت</button>
        </form>
    </div>

    <div class="login-footer">
        <span class="terms-link" onclick="openTerms()">پذیرش قوانین</span>
        <span class="dev-credit">طراحی توسعه : PouyaFakham</span>
        <span class="version-tag">Version 1.1</span>
    </div>

    <div id="termsModal" class="terms-modal-overlay" onclick="closeTerms()">
        <div class="terms-modal" onclick="event.stopPropagation()">
            <div class="terms-header">
                <span class="terms-title">قوانین و مقررات</span>
                <span class="close-terms" onclick="closeTerms()">✕</span>
            </div>
            <div class="terms-body">
                <p>1. رعایت ادب و احترام متقابل در تمامی گفتگوها الزامی است.</p>
                <p>2. ارسال هرگونه محتوای غیراخلاقی، توهین‌آمیز یا خلاف قوانین کشور ممنوع می‌باشد.</p>
                <p>3. مسئولیت تمامی پیام‌های ارسال شده بر عهده کاربر می‌باشد.</p>
                <p>4. استفاده از نام‌های کاربری نامناسب منجر به مسدود شدن حساب کاربری خواهد شد.</p>
                <p>5. ارسال اسپم و پیام‌های رگباری ممنوع است.</p>
                <p>6. این پلتفرم صرفاً جهت ارتباط امن طراحی شده و هرگونه سوءاستفاده پیگرد قانونی دارد.</p>
                <br>
                <p style="text-align:center; color:#818cf8; font-weight:bold;">با ورود به چت، شما این قوانین را می‌پذیرید.</p>
            </div>
        </div>
    </div>
    
    <div id="banModal" class="terms-modal-overlay">
        <div class="ban-modal-content" onclick="event.stopPropagation()">
            <div class="ban-icon">🚫</div>
            <div class="ban-title">دسترسی مسدود شد</div>
            <div id="banMessageDetail" class="ban-message"></div>
            <button class="ban-btn" onclick="location.reload()">تلاش مجدد</button>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let currentCaptchaResult = null;
        let isCodeMode = false;

        const enableIntroPopup = <?php echo isset($enable_intro_popup) && $enable_intro_popup ? 'true' : 'false'; ?>;
        
        window.addEventListener('load', () => {
            if(enableIntroPopup) {
                const seen = localStorage.getItem('bw_intro_seen_v1');
                if(!seen) {
                    const popup = document.getElementById('introPopup');
                    popup.style.display = 'flex';
                    setTimeout(() => popup.classList.add('active'), 100);
                }
            }
        });

        function closeIntroPopup() {
            const popup = document.getElementById('introPopup');
            popup.classList.remove('active');
            localStorage.setItem('bw_intro_seen_v1', 'true');
            setTimeout(() => {
                popup.style.display = 'none';
            }, 400);
        }

        function showToast(msg) { 
            const t=document.getElementById("toast"); 
            t.innerHTML = msg; 
            t.classList.add("show"); 
            setTimeout(()=>t.classList.remove("show"), 4000); 
        }
        
        function refreshBothCaptchas() {
            refreshCaptcha();
            if(isCodeMode) refreshCaptchaCode();
        }

        function refreshCaptcha() {
            const btn = document.querySelector('#mainLoginCard .refresh-btn');
            if(btn) { btn.classList.add('rotating'); setTimeout(() => btn.classList.remove('rotating'), 600); }

            const fd = new FormData(); fd.append('action', 'refresh_captcha'); fd.append('csrf_token', csrfToken);
            fetch('requests.php', { method: 'POST', body: fd }).then(r=>r.json()).then(d=>{ 
                if(d.status === 'success') { 
                    document.getElementById('captchaBox').textContent = `${d.n1} + ${d.n2}`; 
                    document.getElementById('captchaInput').value = ''; 
                }
            });
        }

        function refreshCaptchaCode() {
            const btn = document.querySelector('#loginWithCodeCard .refresh-btn');
            if(btn) { btn.classList.add('rotating'); setTimeout(() => btn.classList.remove('rotating'), 600); }

            const fd = new FormData(); fd.append('action', 'refresh_captcha'); fd.append('csrf_token', csrfToken);
            fetch('requests.php', { method: 'POST', body: fd }).then(r=>r.json()).then(d=>{ 
                if(d.status === 'success') { 
                    document.getElementById('captchaBoxCode').textContent = `${d.n1} + ${d.n2}`; 
                    document.getElementById('captchaInputCode').value = ''; 
                }
            });
        }
        
        let usernameCheckTimeout;
        document.getElementById('username').addEventListener('keyup', function() {
            this.value = this.value.replace(/\s{2,}/g, ' ');

            clearTimeout(usernameCheckTimeout);
            const val = this.value.trim();
            const errBox = document.getElementById('usernameError');
            this.classList.remove('input-error');
            errBox.style.display = 'none';
            document.getElementById('btnWithCode').classList.add('hidden'); 
            
            if(val.length > 2) {
                usernameCheckTimeout = setTimeout(() => {
                    const fd = new FormData();
                    fd.append('action', 'check_username_availability');
                    fd.append('username', val);
                    fd.append('csrf_token', csrfToken);
                    fetch('requests.php', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(d => {
                            if(d.status === 'taken') {
                                document.getElementById('username').classList.add('input-error');
                                errBox.textContent = d.message;
                                errBox.style.display = 'block';
                            }
                        });
                }, 800);
            }
        });

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const fd = new FormData(this); fd.append('csrf_token', csrfToken);
            
            if(document.getElementById('step2').classList.contains('hidden')) {
                fd.append('action', 'login_check');
                fetch('requests.php', { method: 'POST', body: fd }).then(r=>r.json()).then(d=>{
                    if(d.status === 'password_required') { 
                        document.getElementById('step1').classList.add('hidden');
                        document.getElementById('step2').classList.remove('hidden');
                        document.getElementById('password').required = true; 
                        document.getElementById('password').focus(); 
                        document.getElementById('submitBtn').innerText = 'تایید نهایی'; 
                    } else submitLogin(fd);
                });
            } else submitLogin(fd);
        });

        document.getElementById('loginCodeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            document.getElementById('loadingOverlay').style.display = 'flex';
            const fd = new FormData();
            fd.append('action', 'login_with_code');
            fd.append('csrf_token', csrfToken);
            fd.append('username', document.getElementById('codeUsername').value);
            fd.append('security_code', document.getElementById('securityCodeInput').value);
            fd.append('captcha', document.getElementById('captchaInputCode').value);

            fetch('requests.php', { method: 'POST', body: fd }).then(r=>r.json()).then(d=>{
                if(d.status === 'success') {
                    localStorage.removeItem('welcomeShown');
                    setTimeout(()=>location.reload(), 500);
                } else {
                    document.getElementById('loadingOverlay').style.display = 'none';
                    if (d.status === 'banned') {
                        handleBan(d.message, d.ban_type);
                    } else {
                        showToast(d.message); refreshCaptchaCode();
                    }
                }
            }).catch(()=>{ document.getElementById('loadingOverlay').style.display='none'; showToast("خطا در ارتباط"); });
        });
        
        function handleBan(message, type) {
            document.getElementById('banMessageDetail').innerHTML = message ? message.replace(/\n/g, '<br>') : 'دسترسی شما به سیستم محدود شده است.';
            
            const modal = document.getElementById('banModal');
            modal.classList.add('active');
            
            const content = modal.querySelector('.ban-modal-content');
            content.style.background = 'linear-gradient(145deg, #7f1d1d, #450a0a)';
            content.style.borderColor = '#ef4444';
            modal.querySelector('.ban-icon').style.filter = 'drop-shadow(0 0 25px rgba(239, 68, 68, 0.8))';
            
            document.getElementById('loadingOverlay').style.display = 'none';
            
            if (type !== 'perm') {
                setTimeout(() => {
                    const logoutNonce = "<?php echo $_SESSION['logout_nonce'] ?? ''; ?>";
                    window.location.href = 'requests.php?logout=1&nonce=' + logoutNonce;
                }, 5000);
            }
        }
        
        function submitLogin(fd) {
            document.getElementById('loadingOverlay').style.display = 'flex';
            fd.append('action', 'login');
            fetch('requests.php', { method: 'POST', body: fd }).then(r=>r.json()).then(d=>{
                if(d.status === 'success') { 
                    localStorage.removeItem('welcomeShown'); 
                    if(d.security_code) {
                        localStorage.setItem('newSecurityCode', d.security_code);
                    }
                    setTimeout(()=>location.reload(), 500); 
                } 
                else { 
                    document.getElementById('loadingOverlay').style.display = 'none';
                    if (d.status === 'banned') {
                        handleBan(d.message, d.ban_type);
                    } else if(d.status === 'device_mismatch') {
                        showToast(d.message);
                        document.getElementById('btnWithCode').classList.remove('hidden');
                        refreshCaptcha();
                    } else {
                        showToast(d.message); refreshCaptcha(); if(document.getElementById('step2').style.display!='none') document.getElementById('password').value=''; 
                    }
                }
            }).catch(()=>{ document.getElementById('loadingOverlay').style.display='none'; showToast("خطا در ارتباط"); });
        }

        function showLoginWithCode() {
            isCodeMode = true;
            document.getElementById('mainLoginCard').classList.add('hidden');
            document.getElementById('loginWithCodeCard').classList.remove('hidden');
            const mainUser = document.getElementById('username').value;
            const codeUser = document.getElementById('codeUsername');
            codeUser.value = mainUser;
            codeUser.readOnly = true; 
            refreshCaptchaCode();
        }

        function hideLoginWithCode() {
            isCodeMode = false;
            document.getElementById('loginWithCodeCard').classList.add('hidden');
            document.getElementById('mainLoginCard').classList.remove('hidden');
        }

        function openTerms() {
            document.getElementById('termsModal').classList.add('active');
        }
        function closeTerms() {
            document.getElementById('termsModal').classList.remove('active');
        }
    </script>
</body>
</html>
<?php exit; } ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, interactive-widget=resizes-content">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    <meta name="bw-nonce" content="<?php echo isset($_SESSION['bw_nonce']) ? $_SESSION['bw_nonce'] : ''; ?>">
    <meta name="logout-nonce" content="<?php echo $_SESSION['logout_nonce']; ?>">
    <meta name="theme-color" content="#1e293b">
    <link rel="icon" type="image/png" href="<?php echo $favicon_path; ?>">
    <title>چت روم بلک واکر</title>
    <style>
        @font-face { font-family: 'AppFont'; src: url('fonts/<?php echo $font_text; ?>.ttf') format('truetype'); font-weight: normal; }
        @font-face { font-family: 'AppBold'; src: url('fonts/<?php echo $font_heading; ?>.ttf') format('truetype'); font-weight: bold; }
        :root { --bg: #0f172a; --panel: #1e293b; --border: #334155; --primary: #6366f1; --text: #f8fafc; --msg-me: #4f46e5; --msg-other: #334155; }
        * { box-sizing: border-box; font-family: 'AppFont', sans-serif; -webkit-tap-highlight-color: transparent; }
        ::-webkit-scrollbar { display: none; }
        * { -ms-overflow-style: none; scrollbar-width: none; }
        
        body { margin: 0; background: var(--bg); color: var(--text); height: 100vh; height: 100dvh; display: flex; flex-direction: column; overflow: hidden; position: fixed; width: 100%; top: 0; left: 0; -webkit-touch-callout: none; }
        
        .sidebar { position: absolute; right: 0; top: 0; width: 280px; height: 100%; background: var(--panel); border-left: 1px solid var(--border); z-index: 50; transition: transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1); transform: translateX(100%); display: flex; flex-direction: column; box-shadow: -10px 0 30px rgba(0,0,0,0.5); }
        .sidebar.open { transform: translateX(0); }
        .backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(3px); z-index: 40; display: none; opacity: 0; transition: opacity 0.3s; }
        .backdrop.show { display: block; opacity: 1; }
        .close-menu { position: absolute; top: 15px; left: 15px; font-size: 24px; cursor: pointer; color: #94a3b8; padding: 5px; }
        
        @media (min-width: 769px) { 
            body { position: static; flex-direction: row; }
            .sidebar { position: relative; transform: translateX(0); z-index: 10; box-shadow: none; width: 300px; flex-shrink: 0; transition: width 0.4s cubic-bezier(0.2, 0.8, 0.2, 1), transform 0.4s; height: 100%; }
            .sidebar.closed { width: 0; transform: translateX(100%); overflow: hidden; border: none; } 
            .backdrop, .close-menu { display: none !important; } 
        }

        .main { flex: 1; display: flex; flex-direction: column; position: relative; background-image: radial-gradient(#334155 1px, transparent 1px); background-size: 24px 24px; min-width: 0; height: 100%; overflow: hidden; }
        
        .header { height: 60px; background: #1e293b; border-bottom: 1px solid var(--border); display: flex; align-items: center; padding: 0 15px; justify-content: space-between; z-index: 10; box-shadow: none; flex-shrink: 0; }
        .header-title { font-family: 'AppBold'; color: #fff; font-size: 16px; display:flex; flex-direction:column; }
        .header-status { font-size: 11px; color: #94a3b8; }
        
        .social-icons { display: flex; gap: 8px; align-items: center; }
        .social-btn { width: 36px; height: 36px; background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 10px; display: flex; justify-content: center; align-items: center; transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); box-shadow: 0 4px 15px rgba(0,0,0,0.2); text-decoration: none; }
        .social-btn:hover { background: rgba(99, 102, 241, 0.2); border-color: rgba(99, 102, 241, 0.4); transform: translateY(-3px); box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3); }
        .social-btn img { width: 20px; height: 20px; object-fit: contain; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3)); }
        @media (max-width: 400px) {
            .social-btn { width: 30px; height: 30px; border-radius: 8px; }
            .social-btn img { width: 16px; height: 16px; }
            .social-icons { gap: 5px; }
        }

        .search-box { padding: 15px; border-bottom: 1px solid var(--border); background: var(--panel); z-index: 2; flex-shrink: 0; }
        .search-input { width: 100%; background: #0f172a; border: 1px solid var(--border); padding: 10px; border-radius: 10px; color: #fff; font-size: 13px; transition: 0.3s; }
        .search-input.error { border-color: #ef4444; animation: shake 0.4s; }
        @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }

        .list-header { padding: 15px 15px 5px; font-size: 12px; color: #94a3b8; font-weight: bold; }
        .list { overflow-y: auto; flex: 1; padding: 5px 10px; min-height: 0; }
        .item { padding: 10px 12px; margin: 4px 0; border-radius: 10px; cursor: pointer; display: flex; flex-direction: column; align-items: flex-start; justify-content: center; color: #cbd5e1; transition: 0.2s; white-space: nowrap; overflow: hidden; gap: 4px; }
        .item-row { display: flex; align-items: center; justify-content: space-between; width: 100%; }
        .item:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .item.active { background: rgba(99, 102, 241, 0.15); color: #fff; border: 1px solid rgba(99, 102, 241, 0.3); font-family: 'AppBold'; }
        
        .unread-badge { background: #ef4444; color: #fff; min-width: 20px; height: 20px; border-radius: 4px; display: flex; justify-content: center; align-items: center; font-size: 11px; font-weight: bold; padding: 0 6px; box-shadow: 0 2px 5px rgba(239,68,68,0.4); flex-shrink: 0; line-height: 1; }
        
        .messages { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 20px; padding-bottom: 20px; overscroll-behavior: contain; -webkit-overflow-scrolling: touch; overflow-anchor: none; }
        
        .msg { width: fit-content; max-width: 80%; padding: 12px 16px; border-radius: 16px; position: relative; font-size: 14px; line-height: 1.6; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word; box-shadow: 0 3px 8px rgba(0,0,0,0.15); cursor: pointer; transition: transform 0.1s; margin-bottom: 5px; -webkit-tap-highlight-color: transparent !important; user-select: none; -webkit-user-select: none; }
        
        .msg.me { align-self: flex-end; margin-right: auto; margin-left: 0; background: var(--msg-me); color: #fff; border-bottom-left-radius: 4px; border-bottom-right-radius: 16px; }
        .msg.other { align-self: flex-start; margin-left: auto; margin-right: 0; background: var(--msg-other); color: #e2e8f0; border-bottom-right-radius: 4px; border-bottom-left-radius: 16px; }
        
        .msg.system { align-self: center; background: rgba(255, 255, 255, 0.1); color: #94a3b8; font-size: 12px; padding: 5px 12px; border-radius: 20px; box-shadow: none; border: 1px solid rgba(255,255,255,0.1); margin: 10px 0; text-align: center; max-width: 90%; }

        .text-glass-box {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 12px 16px;
            border-radius: 14px;
            margin-top: 8px;
            display: block;
            user-select: text;
            -webkit-user-select: text;
            line-height: 1.7;
            box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.1), 0 4px 15px rgba(0,0,0,0.1);
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .msg.pending { opacity: 0.7; }
        .msg.error { border: 1px solid #ef4444; }
        
        .msg.highlight-target { animation: replyPulse 2s cubic-bezier(0.4, 0, 0.2, 1); z-index: 10; }
        @keyframes replyPulse {
            0% { filter: brightness(1.5); box-shadow: 0 0 0 3px #818cf8, 0 0 20px #818cf8; transform: scale(1.02); }
            100% { filter: brightness(1); box-shadow: 0 3px 8px rgba(0,0,0,0.15); transform: scale(1); }
        }

        .sender { font-size: 10px; opacity: 0.7; margin-bottom: 6px; font-weight: bold; display: flex; align-items: center; gap: 5px; }
        .chat-text { margin-top: 5px; display: block; user-select: text; -webkit-user-select: text; }
        .retry-btn { font-size: 11px; color: #ef4444; cursor: pointer; font-weight: bold; margin-top: 5px; display: block; text-align: right; }
        .media-container { max-width: 100%; border-radius: 12px; overflow: hidden; margin-top: 5px; background: rgba(0,0,0,0.2); }
        .chat-media { width: auto; max-width: 100%; max-height: 300px; object-fit: contain; display: block; }
        audio { width: 260px; max-width: 100%; height: 40px; border-radius: 20px; margin-top: 5px; display: block; outline: none; }
        .file-box { display: flex; align-items: center; background: rgba(0,0,0,0.2); border-radius: 12px; padding: 10px; margin-top: 5px; gap: 10px; min-width: 150px; }
        .file-icon { font-size: 24px; }
        .file-info { flex: 1; overflow: hidden; }
        .file-name { font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; font-weight: bold; }
        .download-btn { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #fff; width: 36px; height: 36px; border-radius: 50%; display: flex; justify-content: center; align-items: center; text-decoration: none; transition: 0.2s; font-size: 18px; flex-shrink: 0; }
        .download-btn:hover { background: var(--primary); border-color: var(--primary); }
        
        .input-box { background: var(--panel); padding: 10px 15px; border-top: 1px solid var(--border); z-index: 20; flex-shrink: 0; padding-bottom: calc(10px + env(safe-area-inset-bottom)); }
        
        .reply-bar, .file-preview { background: #0f172a; padding: 8px 12px; border-left: 3px solid var(--primary); margin-bottom: 8px; border-radius: 8px; display: none; justify-content: space-between; align-items: center; font-size: 12px; color: #cbd5e1; }
        .input-row { display: flex; gap: 8px; align-items: center; }
        textarea { flex: 1; background: #334155; border: none; color: white; padding: 12px 15px; border-radius: 20px; resize: none; height: 46px; outline: none; font-size: 14px; transition: 0.2s; }
        textarea:focus { background: #475569; }
        .btn { width: 46px; height: 46px; border-radius: 50%; border: none; background: #334155; color: #cbd5e1; cursor: pointer; display: flex; justify-content: center; align-items: center; font-size: 20px; transition: 0.2s; flex-shrink: 0; }
        .btn:hover { background: #475569; color: #fff; }
        .btn-send { background: var(--primary); color: white; }
        .btn-send:hover { background: #4f46e5; }
        .voice-rec { display: none; flex: 1; align-items: center; justify-content: space-between; background: #334155; height: 46px; border-radius: 23px; padding: 0 20px; color: #ef4444; border: 1px solid #ef4444; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); } 100% { box-shadow: 0 0 0 10px rgba(239,68,68,0); } }
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 2000; display: flex; justify-content: center; align-items: center; backdrop-filter: blur(8px); opacity: 0; transition: opacity 0.3s; visibility: hidden; }
        .modal-overlay.active { opacity: 1; visibility: visible; }
        .modal { background: #1e293b; padding: 30px; border-radius: 24px; width: 90%; max-width: 380px; text-align: center; border: 1px solid #334155; box-shadow: 0 20px 60px rgba(0,0,0,0.7); transform: scale(0.9); transition: transform 0.3s; }
        .modal-overlay.active .modal { transform: scale(1); }
        .modal-title { color: #fff; font-size: 18px; margin-bottom: 10px; font-family: 'AppBold'; }
        .modal-text { color: #94a3b8; font-size: 14px; margin-bottom: 25px; line-height: 1.6; }
        .modal-icon { font-size: 50px; margin-bottom: 20px; display: block; }
        .modal-input { width: 100%; background: #0f172a; border: 1px solid #334155; color: #fff; padding: 12px; border-radius: 12px; margin: 10px 0; outline: none; text-align: center; font-size: 16px; }
        .modal-btn { width: 100%; padding: 12px; border-radius: 12px; border: none; font-weight: bold; cursor: pointer; margin-top: 10px; font-family: 'AppBold'; font-size: 15px; }
        .btn-green { background: #10b981; color: #fff; }
        .btn-red { background: #ef4444; color: #fff; }
        .btn-gray { background: #334155; color: #cbd5e1; }
        .notif-popup { position: fixed; top: 70px; left: 50%; transform: translateX(-50%) translateY(-200%); background: rgba(30, 41, 59, 0.98); border: 1px solid #6366f1; border-radius: 16px; padding: 15px 20px; z-index: 99999; box-shadow: 0 15px 40px rgba(0,0,0,0.6); display: flex; align-items: center; gap: 15px; transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); min-width: 320px; max-width: 90%; backdrop-filter: blur(12px); cursor: pointer; opacity: 0; visibility: hidden; }
        .notif-popup.show { transform: translateX(-50%) translateY(0); opacity: 1; visibility: visible; }
        .notif-avatar { width: 45px; height: 45px; background: #334155; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 24px; border: 2px solid #6366f1; flex-shrink: 0; }
        .notif-content { flex: 1; overflow: hidden; }
        .notif-name { font-weight: bold; color: #fff; font-size: 14px; margin-bottom: 3px; display: block; }
        .notif-text { color: #cbd5e1; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
        .notif-hint { color: #818cf8; font-size: 11px; margin-top: 4px; font-weight: bold; display: block; }
        .msg-menu-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 2500; display: none; opacity: 0; transition: opacity 0.2s; align-items: flex-end; justify-content: center; }
        .msg-menu-overlay.active { opacity: 1; display: flex; }
        .msg-menu { background: #1e293b; width: 100%; max-width: 500px; border-radius: 20px 20px 0 0; padding: 25px; transform: translateY(100%); transition: transform 0.3s cubic-bezier(0.1, 0.7, 0.1, 1); box-shadow: 0 -10px 40px rgba(0,0,0,0.5); border-top: 1px solid #334155; padding-bottom: 40px; position: relative; z-index: 2501; }
        .msg-menu.active { transform: translateY(0); }
        .menu-grid-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 15px; }
        .action-item { background: rgba(255,255,255,0.05); border-radius: 15px; padding: 20px 10px; text-align: center; cursor: pointer; transition: 0.2s; display: flex; flex-direction: column; align-items: center; gap: 10px; color: #e2e8f0; font-size: 13px; border: 1px solid rgba(255,255,255,0.05); }
        .action-item:active { transform: scale(0.95); background: rgba(255,255,255,0.1); }
        .action-icon { font-size: 28px; margin-bottom: 5px; }
        .action-cancel { width: 100%; padding: 15px; background: #0f172a; border-radius: 15px; border: none; color: #94a3b8; font-weight: bold; margin-top: 15px; cursor: pointer; font-size: 14px; }
        
        .menu-grid { display: flex; flex-wrap: wrap; gap: 8px; padding: 10px; margin-top: auto; border-top: 1px solid var(--border); background: var(--panel); z-index: 3; flex-shrink: 0; }
        .menu-item { background: rgba(15, 23, 42, 0.5); border: 1px solid var(--border); padding: 10px; border-radius: 12px; text-align: center; font-size: 11px; cursor: pointer; color: #cbd5e1; display: flex; align-items: center; justify-content: center; min-height: 42px; flex: 1 1 calc(50% - 8px); }
        .menu-item.full-width { flex: 1 1 100%; background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(185, 28, 28, 0.2)); color: #fca5a5; border-color: rgba(239, 68, 68, 0.3); font-weight: bold; }
        .menu-toggle-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; background: rgba(15, 23, 42, 0.5); border: 1px solid var(--border); border-radius: 12px; color: #cbd5e1; font-size: 11px; cursor: pointer; min-height: 42px; flex: 1 1 calc(50% - 8px); }
        .menu-toggle-item.full-width { flex: 1 1 100%; }

        .ctx-menu, .msg-ctx-menu { position: fixed; background: #1e293b; border: 1px solid #334155; border-radius: 10px; padding: 5px; z-index: 9999; display: none; min-width: 150px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .ctx-item { padding: 8px 12px; cursor: pointer; color: #e2e8f0; font-size: 13px; border-radius: 6px; display: flex; align-items: center; justify-content: space-between; }
        .ctx-item:hover { background: #334155; color: #fff; }
        
        .typing-indicator { 
            align-self: flex-start; 
            margin-left: auto;      
            margin-right: 0; 
            background: var(--msg-other);
            color: #e2e8f0;
            padding: 10px 16px;
            border-radius: 16px;
            font-size: 13px;
            display: none;
            margin-bottom: 5px;
            border-bottom-right-radius: 4px;
            border-bottom-left-radius: 16px;
            animation: fadeIn 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .typing-indicator::after {
            content: '...';
            animation: typing 1.5s infinite;
        }
        @keyframes typing {
            0% { content: '.'; }
            33% { content: '..'; }
            66% { content: '...'; }
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        .reply-content { background: rgba(255,255,255,0.1); border-right: 3px solid #6366f1; padding: 8px 12px; border-radius: 8px; margin-bottom: 8px; display: flex; flex-direction: column; cursor: pointer; font-size: 11px; position: relative; overflow: hidden; -webkit-tap-highlight-color: transparent !important; user-select: none; -webkit-user-select: none; }
        .reply-sender { font-weight: bold; color: #a5b4fc; margin-bottom: 3px; font-size: 12px; }
        .reply-text { color: #cbd5e1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        
        .disabled-overlay { position:absolute; inset:0; background:rgba(15,23,42,0.8); z-index:30; display:flex; justify-content:center; align-items:center; color:#94a3b8; font-size:12px; backdrop-filter:blur(2px); border-radius:20px; }
        
        .user-info-col { display: flex; flex-direction: column; justify-content: center; }
        .user-status-sub { font-size: 10px; margin-top: 3px; font-weight: normal; }
        .status-online { color: #34d399; } 
        .status-offline { color: #94a3b8; } 
        
        .found-room-card { background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(6, 95, 70, 0.2)); border: 1px solid rgba(16, 185, 129, 0.4); padding: 12px; border-radius: 12px; cursor: pointer; margin-top: 10px; transition: transform 0.2s; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .found-room-card:active { transform: scale(0.98); }
        .found-room-content { display: flex; justify-content: space-between; align-items: center; }
        .found-room-info { display: flex; flex-direction: column; gap: 4px; }
        .found-room-name { font-weight: bold; color: #34d399; font-size: 14px; }
        .found-room-action { background: #10b981; color: white; padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: bold; box-shadow: 0 2px 5px rgba(16, 185, 129, 0.3); }

        .copy-code-box { display: flex; align-items: center; justify-content: space-between; background: rgba(99,102,241,0.2); border: 1px dashed #6366f1; border-radius: 10px; padding: 10px 15px; margin-bottom: 20px; }
        .code-text { font-family: monospace; font-size: 24px; color: #fff; letter-spacing: 2px; }
        .copy-btn { background: rgba(255,255,255,0.1); border: none; color: #fff; width: 36px; height: 36px; border-radius: 8px; cursor: pointer; display: flex; justify-content: center; align-items: center; transition: 0.2s; }
        .copy-btn:hover { background: #6366f1; }
        
        .code-display-small { font-size: 10px; color: #94a3b8; background: rgba(0,0,0,0.2); padding: 2px 6px; border-radius: 4px; cursor: pointer; margin-top: 4px; display: inline-block; font-family: monospace; border: 1px dashed rgba(255,255,255,0.1); }
        .code-display-small:hover { background: rgba(99,102,241,0.2); color: #fff; border-color: #6366f1; }
        
        .toggle-switch { position: relative; width: 34px; height: 18px; }
        .toggle-switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #334155; transition: .4s; border-radius: 20px; }
        .slider:before { position: absolute; content: ""; height: 14px; width: 14px; left: 2px; bottom: 2px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #10b981; }
        input:checked + .slider:before { transform: translateX(16px); }
        
        .room-code-badge { font-size: 10px; color: #a5b4fc; background: rgba(99,102,241,0.1); padding: 2px 6px; border-radius: 4px; border: 1px dashed rgba(99,102,241,0.3); margin-right: auto; cursor: pointer; font-family: monospace; }
        .room-code-badge:hover { background: rgba(99,102,241,0.3); color: #fff; }
        
        .drag-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 9999; display: none; justify-content: center; align-items: center; flex-direction: column; font-size: 24px; color: #fff; border: 5px dashed #6366f1; backdrop-filter: blur(5px); }
        .drag-overlay.active { display: flex; }
        .drag-text { margin-top: 20px; font-weight: bold; }
        .drag-icon { font-size: 60px; animation: bounce 1s infinite; }
        @keyframes bounce { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }

        .captcha-row { display: flex; gap: 10px; margin-bottom: 15px; }
        .captcha-text { flex: 1; height: 50px; background: rgba(99, 102, 241, 0.1); border: 1px dashed rgba(99, 102, 241, 0.4); border-radius: 14px; display: flex; justify-content: center; align-items: center; font-weight: bold; color: #818cf8; font-size: 20px; font-family: 'AppBold'; overflow: hidden; white-space: nowrap; user-select: none; }
        .refresh-btn { width: 50px; height: 50px; background: rgba(15, 23, 42, 0.7); border: 1px solid #334155; border-radius: 14px; color: #94a3b8; cursor: pointer; display: flex; justify-content: center; align-items: center; font-size: 22px; transition: 0.3s; }
        .refresh-btn.rotating { animation: spin-icon 0.6s ease-in-out; background: rgba(99, 102, 241, 0.3); color: #fff; border-color: #818cf8; }
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }

        #reportUserSuggestions {
            position: absolute;
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid #475569;
            border-radius: 12px;
            width: calc(100% - 60px);
            max-height: 150px;
            overflow-y: auto;
            z-index: 3000;
            display: none;
            margin-top: -10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }
        #reportUserSuggestions.show {
            display: block;
        }
        .suggestion-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            color: #e2e8f0;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.2s;
        }
        .suggestion-item:last-child {
            border-bottom: none;
        }
        .suggestion-item:hover {
            background: rgba(99, 102, 241, 0.2);
            color: #fff;
        }

        .scroll-controls { position: absolute; bottom: 85px; right: 20px; display: flex; flex-direction: column; gap: 10px; z-index: 900; pointer-events: none; }
        .scroll-btn { width: 40px; height: 40px; background: rgba(30, 41, 59, 0.8); backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.1); border-radius: 50%; color: #fff; display: flex; justify-content: center; align-items: center; cursor: pointer; pointer-events: auto; transition: 0.3s; opacity: 0; visibility: hidden; transform: scale(0.8); box-shadow: 0 5px 15px rgba(0,0,0,0.3); position: relative; }
        .scroll-btn.visible { opacity: 1; visibility: visible; transform: scale(1); }
        .scroll-btn:hover { background: var(--primary); border-color: var(--primary); }
        .scroll-btn svg { width: 20px; height: 20px; fill: currentColor; }

        .ban-modal-content { background: rgba(50, 0, 0, 0.8); backdrop-filter: blur(20px); border: 1px solid rgba(239, 68, 68, 0.4); background-image: linear-gradient(145deg, rgba(69, 10, 10, 0.9), rgba(26, 5, 5, 0.95)); border-radius: 30px; padding: 40px 30px; text-align: center; width: 100%; max-width: 450px; box-shadow: 0 25px 60px rgba(0,0,0,0.8); position: relative; overflow: hidden; margin: 0 auto; }
        .ban-modal-content::after { content: ''; position: absolute; inset: 0; border-radius: 30px; padding: 2px; background: linear-gradient(45deg, transparent, rgba(239, 68, 68, 0.3), transparent); -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0); -webkit-mask-composite: xor; mask-composite: exclude; pointer-events: none; }
        .ban-icon { font-size: 65px; margin-bottom: 20px; display: inline-block; filter: drop-shadow(0 0 25px rgba(239, 68, 68, 0.5)); animation: pulseBan 2s infinite; }
        .ban-title { color: #ef4444; font-size: 24px; margin-bottom: 15px; font-family: 'AppBold'; text-shadow: 0 0 20px rgba(239, 68, 68, 0.4); }
        .ban-message { color: #cbd5e1; font-size: 15px; line-height: 1.8; margin-bottom: 30px; background: rgba(0,0,0,0.25); padding: 20px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.05); }
        .ban-btn { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 12px 30px; border-radius: 12px; cursor: pointer; transition: 0.3s; font-family: 'AppBold'; font-size: 14px; }
        .ban-btn:hover { background: rgba(255,255,255,0.2); transform: translateY(-2px); }
        @keyframes pulseBan { 0% { transform: scale(1); } 50% { transform: scale(1.05); filter: drop-shadow(0 0 35px rgba(239, 68, 68, 0.7)); } 100% { transform: scale(1); } }
        
        #toast { position: fixed; top: 20px; left: 50%; transform: translateX(-50%) translateY(-20px); background: rgba(239, 68, 68, 0.95); backdrop-filter: blur(8px); color: white; padding: 12px 24px; border-radius: 16px; font-size: 14px; opacity: 0; transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); pointer-events: none; z-index: 9999; text-align: center; max-width: 90%; width: max-content; box-shadow: 0 10px 30px rgba(239, 68, 68, 0.3); line-height: 1.6; border: 1px solid rgba(255,255,255,0.2); }
        #toast.show { opacity: 1; transform: translateX(-50%) translateY(20px); }

        .load-more-box { background: rgba(99, 102, 241, 0.1); border: 1px dashed rgba(99, 102, 241, 0.4); color: #818cf8; padding: 10px 15px; text-align: center; border-radius: 12px; cursor: pointer; font-family: 'AppBold'; font-size: 13px; margin: 0 auto 20px auto; width: max-content; min-width: 200px; transition: all 0.3s ease; user-select: none; }
        .load-more-box:hover { background: rgba(99, 102, 241, 0.2); border-color: #6366f1; color: #fff; }

        @keyframes spin-circle { 100% { transform: rotate(360deg); } }

        .history-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.9); z-index: 99999; display: flex; justify-content: center; align-items: center; flex-direction: column; backdrop-filter: blur(8px); opacity: 0; visibility: hidden; transition: all 0.3s ease; }
        .history-overlay.active { opacity: 1; visibility: visible; }
        .history-spinner { width: 50px; height: 50px; border: 4px solid rgba(99, 102, 241, 0.2); border-top-color: #6366f1; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 20px; box-shadow: 0 0 15px rgba(99, 102, 241, 0.4); }
        .history-text { color: #fff; font-family: 'AppBold'; font-size: 16px; text-shadow: 0 2px 10px rgba(0,0,0,0.5); animation: pulseText 1.5s infinite; }
        @keyframes pulseText { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
    </style>
</head>
<body oncontextmenu="return false;" onclick="hideCtx()">

<div id="historyOverlay" class="history-overlay">
    <div class="history-spinner"></div>
    <div class="history-text">در حال بارگذاری تاریخچه پیام‌ها...</div>
</div>

<div id="dragOverlay" class="drag-overlay">
    <div class="drag-icon">📂</div>
    <div class="drag-text">فایل‌ها را اینجا رها کنید (حداکثر ۵ فایل)</div>
</div>

<div id="welcomeModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-icon">👋</div>
        <div class="modal-title">به بلک واکر خوش آمدید</div>
        <p class="modal-text">لطفاً جهت حفظ محیطی امن، قوانین را رعایت کنید:</p>
        <ul style="text-align: right; margin-bottom: 25px; color: #cbd5e1; font-size: 13px; padding-right: 25px; line-height: 1.8;">
            <li>🔹 رعایت ادب و احترام متقابل</li>
            <li>🔹 عدم ارسال محتوای غیراخلاقی</li>
            <li>🔹 جلوگیری از ارسال اسپم</li>
        </ul>
        <button class="modal-btn btn-green" onclick="localStorage.setItem('welcomeShown','true'); closeModal('welcomeModal'); checkAndShowSecurityCode();">متوجه شدم</button>
    </div>
</div>

<div id="securityCodeModal" class="modal-overlay">
    <div class="modal" style="border-color:#10b981; background: linear-gradient(145deg, #1e293b, #064e3b);">
        <div class="modal-icon">🔐</div>
        <div class="modal-title" style="color:#10b981">کد امنیتی اکانت شما</div>
        <p class="modal-text" style="color:#fff; font-size:14px; line-height:1.8">
            این کد اختصاصی برای شما ساخته شده است. <br>
            <span style="color:#fca5a5; font-weight:bold;">توجه:</span> اگر با دستگاه دیگری قصد ورود داشتید یا نشست شما پاک شد، این کد تنها راه بازگردانی اکانت شماست! حتماً آن را ذخیره کنید.
        </p>
        <div class="copy-code-box" style="margin-top:15px; border-color:#10b981; background:rgba(16,185,129,0.2);">
            <span id="newSecurityCodeDisplay" class="code-text" style="color:#34d399"></span>
            <button class="copy-btn" style="background:#10b981" onclick="copyNewSecurityCode()">📋</button>
        </div>
        <button class="modal-btn btn-gray" onclick="closeModal('securityCodeModal')">ذخیره کردم</button>
    </div>
</div>

<div id="globalAlertModal" class="modal-overlay">
    <div class="modal" style="border-color:#f59e0b; background: linear-gradient(145deg, #1e293b, #1e1b15);">
        <div class="modal-icon">📢</div>
        <div class="modal-title" style="color:#f59e0b">اطلاعیه سراسری</div>
        <p id="globalAlertText" class="modal-text" style="color:#fff; font-weight:bold; font-size:15px;"></p>
        <button class="modal-btn btn-gray" onclick="closeModal('globalAlertModal')">خواندم</button>
    </div>
</div>

<div id="banModal" class="modal-overlay" style="z-index: 99999;">
    <div class="ban-modal-content">
        <div class="ban-icon">🚫</div>
        <div class="ban-title">دسترسی مسدود شد</div>
        <div id="banMessageDetail" class="ban-message">
            متاسفانه حساب کاربری شما مسدود شده است.
        </div>
        <button class="ban-btn" onclick="location.reload()">تلاش مجدد</button>
    </div>
</div>

<div id="reportUserModal" class="modal-overlay">
    <div class="modal" style="position:relative;">
        <div class="modal-title">گزارش تخلف کاربر</div>
        <p class="modal-text">لطفاً نام کاربر و دلیل گزارش را وارد کنید.</p>
        
        <input type="text" id="reportTargetUser" class="modal-input" placeholder="جستجوی نام کاربری..." autocomplete="off">
        <div id="reportUserSuggestions"></div>
        
        <textarea id="reportReason" class="modal-input" style="height:80px;resize:none;" placeholder="دلیل گزارش..."></textarea>
        
        <div class="captcha-row" style="margin-top:10px;">
            <div class="captcha-text" id="reportCaptchaBox" style="font-size:16px;">Loading...</div>
            <div class="refresh-btn" onclick="refreshReportCaptcha()">↻</div>
        </div>
        <input type="number" id="reportCaptchaInput" class="modal-input" placeholder="کد امنیتی را وارد کنید" required>
        
        <div style="display:flex; gap:10px; margin-top:10px;">
            <button onclick="submitReportUser()" class="modal-btn btn-red" id="btnReportSend">ارسال گزارش</button>
            <button onclick="closeModal('reportUserModal')" class="modal-btn btn-gray">لغو</button>
        </div>
    </div>
</div>

<div id="msgMenu" class="msg-menu-overlay" onclick="closeMsgMenu()">
    <div class="msg-menu" onclick="event.stopPropagation()">
        <div style="text-align:center; color:#94a3b8; font-size:14px; margin-bottom:20px; font-weight:bold;" id="msgMenuTitle">مدیریت پیام</div>
        <div class="menu-grid-actions" id="msgActionsGrid"></div>
        <button class="action-cancel" onclick="closeMsgMenu()">انصراف</button>
    </div>
</div>

<div id="accessDeniedModal" class="modal-overlay">
    <div class="modal" style="border-color:#f59e0b;">
        <div class="modal-icon">🔒</div>
        <div class="modal-title" style="color:#f59e0b">عدم دسترسی</div>
        <p class="modal-text">شما مجوز ورود به این اتاق خصوصی را ندارید.</p>
        <button class="modal-btn btn-gray" onclick="closeModal('accessDeniedModal'); switchRoom('گفتگوی عمومی', 'گفتگوی عمومی', null);">بازگشت به عمومی</button>
    </div>
</div>

<div id="modalConfirm" class="modal-overlay">
    <div class="modal">
        <div class="modal-icon">🤔</div>
        <div class="modal-title">تایید عملیات</div>
        <p id="confirmMsg" class="modal-text"></p>
        <div style="display:flex; gap:10px;">
            <button class="modal-btn btn-green" id="confirmYes">بله</button>
            <button class="modal-btn btn-gray" onclick="closeModal('modalConfirm')">خیر</button>
        </div>
    </div>
</div>

<div id="modalPrompt" class="modal-overlay">
    <div class="modal">
        <div class="modal-icon">✏️</div>
        <div id="promptMsg" class="modal-title"></div>
        <input type="text" id="promptInput" class="modal-input">
        <div style="display:flex; gap:10px;">
            <button class="modal-btn btn-green" id="promptOk">تایید</button>
            <button class="modal-btn btn-gray" onclick="closeModal('modalPrompt')">لغو</button>
        </div>
    </div>
</div>

<div id="roomSelectModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-title">انتخاب اتاق برای دعوت</div>
        <div id="inviteRoomList" style="max-height:200px; overflow-y:auto; margin:15px 0;"></div>
        <button onclick="closeModal('roomSelectModal')" class="modal-btn btn-gray">لغو</button>
    </div>
</div>

<div id="createRoomModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-icon">💬</div>
        <div class="modal-title">ساخت اتاق جدید</div>
        <p class="modal-text">شما فقط می‌توانید یک اتاق خصوصی بسازید.</p>
        <input type="text" id="newRoomName" class="modal-input" placeholder="نام اتاق (۴ تا ۱۰ حرف)..." maxlength="10">
        <div style="display:flex; gap:10px; margin-top:10px;">
            <button id="btnCreateRoomSubmit" onclick="submitCreateRoom(event)" class="modal-btn btn-green">ساخت</button>
            <button onclick="closeModal('createRoomModal')" class="modal-btn btn-gray">لغو</button>
        </div>
    </div>
</div>

<div id="showCodeModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-icon">🎉</div>
        <div class="modal-title">اتاق ساخته شد!</div>
        <p class="modal-text">کد دعوت اتاق شما:</p>
        <div class="copy-code-box">
            <span id="createdCodeDisplay" class="code-text"></span>
            <button class="copy-btn" onclick="copyInviteCode()">📋</button>
        </div>
        <button onclick="closeModal('showCodeModal')" class="modal-btn btn-gray">متوجه شدم</button>
    </div>
</div>

<div id="notifPopup" class="notif-popup" onclick="handleNotifClick()">
    <div class="notif-avatar" id="notifAvatar"></div>
    <div class="notif-content">
        <div class="notif-name" id="notifName"></div>
        <div class="notif-text" id="notifText"></div>
        <div class="notif-hint">ورود به گپ ↩</div>
    </div>
</div>

<div id="ctxMenu" class="ctx-menu">
    <div class="ctx-item" onclick="handleCtx('private')">💬 پیام خصوصی</div>
    <div class="ctx-item" id="ctxInvite" onclick="handleCtx('invite')">📩 دعوت به اتاق</div>
    <div class="ctx-item" id="ctxBan" style="color:#fca5a5" onclick="handleCtx('ban')">🚫 مسدود کردن</div>
</div>

<div id="msgCtxMenu" class="msg-ctx-menu">
    <div class="ctx-item" onclick="handleMsgCtxAction('reply')">↩ پاسخ</div>
    <div class="ctx-item" id="ctxEditMsg" onclick="handleMsgCtxAction('edit')">✎ ویرایش</div>
    <div class="ctx-item" onclick="handleMsgCtxAction('copy')">📋 کپی</div>
    <div class="ctx-item" id="ctxDelMsg" style="color:#fca5a5" onclick="handleMsgCtxAction('delete')">🗑 حذف</div>
</div>

<div class="backdrop" onclick="toggleMenu()"></div>

<div class="sidebar" id="sidebar">
    <div class="close-menu" onclick="toggleMenu()">✕</div>
    <div class="search-box">
        <input type="text" class="search-input" placeholder="جستجو (یا وارد کردن کد دعوت)..." onkeyup="handleSearch(this.value)">
    </div>
    
    <div class="list" id="sidebarList"></div>
    
    <div class="menu-grid">
        <div class="menu-toggle-item <?php echo $isReportLocked ? 'full-width' : ''; ?>">
            <span>نوتیفیکیشن</span>
            <label class="toggle-switch">
                <input type="checkbox" id="notifToggle" onchange="toggleNotifications(this)">
                <span class="slider"></span>
            </label>
        </div>
        <?php if (!$isReportLocked): ?>
        <div class="menu-toggle-item" style="cursor:pointer;" onclick="openReportModal()">
            <span>گزارش کاربر</span>
            <span style="font-size:16px;">⚠️</span>
        </div>
        <?php endif; ?>
        <div class="menu-item" onclick="showMySecurityCode()">🔐 کد امنیتی من</div>
        <div class="menu-item" onclick="askDeleteAll()">🗑 حذف پیام‌های من</div>
        <div class="menu-item" onclick="openCreateRoom()">💬 ساخت اتاق شخصی</div>
        <div class="menu-item full-width" onclick="askLogout()">خروج از حساب</div>
        <div class="menu-item full-width" id="adminPanelBtn" style="display:none; background:rgba(99,102,241,0.2); color:#a5b4fc" onclick="location.href='admin.php'">⚙ پنل مدیریت پیشرفته</div>
    </div>
</div>

<div class="main">
    <div class="header">
        <div style="display:flex; align-items:center; gap:15px;">
            <button class="btn" onclick="toggleMenu()" style="width:35px;height:35px;background:transparent;font-size:22px;">☰</button>
            <div>
                <span id="chatTitle" class="header-title"></span>
                <span id="chatStatus" class="header-status"></span>
            </div>
        </div>
        <div class="social-icons">
            <?php if(!empty($social_github_link)): ?>
            <a href="<?php echo $social_github_link; ?>" target="_blank" class="social-btn" title="GitHub">
                <img src="<?php echo $social_github_icon; ?>" alt="GitHub">
            </a>
            <?php endif; ?>
            <?php if(!empty($social_website_link)): ?>
            <a href="<?php echo $social_website_link; ?>" target="_blank" class="social-btn" title="Website">
                <img src="<?php echo $social_website_icon; ?>" alt="Website">
            </a>
            <?php endif; ?>
            <?php if(!empty($social_telegram_link)): ?>
            <a href="<?php echo $social_telegram_link; ?>" target="_blank" class="social-btn" title="Telegram">
                <img src="<?php echo $social_telegram_icon; ?>" alt="Telegram">
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="messages" id="messagesBox">
        <div id="loadMoreBtn" class="load-more-box" onclick="loadMoreMessages()" style="display:none;">بارگذاری پیام‌های قبلی ⟳</div>
        <div id="msgsContent"></div>
        <div class="typing-indicator" id="typingStatus">در حال نوشتن</div>
    </div>

    <div class="scroll-controls">
        <div class="scroll-btn up" onclick="scrollToTop()">
            <svg viewBox="0 0 24 24"><path d="M12 8l6 6H6z"/></svg>
        </div>
        <div class="scroll-btn down" id="scrollDownBtn" onclick="scrollToBottom()">
            <svg viewBox="0 0 24 24"><path d="M12 16l-6-6h12z"/></svg>
            <span id="scrollUnreadBadge" style="display:none; position:absolute; top:-6px; right:-6px; background:#ef4444; color:#fff; font-size:11px; border-radius:4px; min-width:22px; height:22px; justify-content:center; align-items:center; font-weight:bold; box-shadow: 0 2px 5px rgba(239,68,68,0.5); padding:0 4px;"></span>
        </div>
    </div>

    <div class="input-box" id="inputBox">
        <div class="file-preview" id="filePreview">
            <span style="display:flex;align-items:center;gap:5px;">📎 <b id="fileName" style="color:#fff"></b></span>
            <span onclick="cancelFile()" style="cursor:pointer; color:#ef4444">✕</span>
        </div>
        <div class="reply-bar" id="replyBar">
            <div style="display:flex; flex-direction:column; width:100%">
                <span style="color:#818cf8; font-weight:bold; font-size:11px;">پاسخ به:</span>
                <span id="replyText" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-size:12px;"></span>
            </div>
            <span onclick="cancelReply()" style="cursor:pointer; color:#ef4444; padding:5px;">✕</span>
        </div>

        <div class="input-row" id="inputRow">
            <input type="file" id="fileInput" hidden multiple accept="*/*">
            <button class="btn" id="attachBtn" onclick="document.getElementById('fileInput').click()">📎</button>
            <button class="btn" id="micBtn" style="touch-action:none;">🎤</button>
            <textarea id="msgInput" placeholder="پیام..." maxlength="1000"></textarea>
            <button class="btn btn-send" onclick="sendMessage()">➤</button>
        </div>
        
        <div class="voice-rec" id="voiceUI">
            <span style="font-size:11px;opacity:0.8">برای قفل بالا بکشید</span>
            <span id="recTimer" style="font-family:monospace;font-size:16px">00:00</span>
            <button onclick="cancelVoice()" style="background:none;border:none;color:#fff;cursor:pointer">لغو</button>
            <button id="sendVoiceBtn" onclick="stopRecManual()" style="display:none;width:36px;height:36px;border-radius:50%;background:#fff;border:none;color:#ef4444">➤</button>
        </div>
    </div>
</div>

<script>
let csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
let bwNonce = document.querySelector('meta[name="bw-nonce"]').getAttribute('content');
let logoutNonce = document.querySelector('meta[name="logout-nonce"]').getAttribute('content');
let currentRoom = 'گفتگوی عمومی', currentRoomId = null, targetUser = null, currentUser = null, isAdmin = false;
let currentUsername = '';
let replyToId = null, mediaRecorder, audioChunks=[], isRecording=false, recInterval, isLocked=false, startY=0;
let ctxId=null, ctxName=null, selectedMsgId=null, selectedMsgSender=null, selectedMsgText=null;
let lastMsgId = 0, notifQueue = [], isShowingNotif = false, notifData = null;
let lastUnreadCounts = {}; 
let myInvites = [];
let allRoomsCache = [];
let allUsersCache = [];
let replyContent = null;
let replyName = null;
let shownNotificationIds = new Set();
let oldestMsgId = null;
let typingTimeout;
let msgCache = {};
let searchTimeout;
let lastJoinAttempt = 0;
let notificationsEnabled = true;
let uploadQueue = [];
let isUploading = false;
let isLoadingMore = false;
let newMessagesSinceScroll = 0;
let roomUnreadLocal = {}; 
let loadedHistoryHtml = '';
const MAX_FILE_SIZE = <?php echo $file_size_limit; ?>;

if (window.visualViewport) {
    window.visualViewport.addEventListener('resize', () => {
        document.body.style.height = window.visualViewport.height + 'px';
        const box = document.getElementById('messagesBox');
        if (box) {
            box.scrollTop = box.scrollHeight; 
        }
    });
}

function escapeHTML(str) {
    if (!str) return '';
    return str.replace(/[&<>'"]/g, function(tag) {
        const charsToReplace = { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' };
        return charsToReplace[tag] || tag;
    });
}

function showMySecurityCode() {
    document.getElementById('newSecurityCodeDisplay').textContent = '<?php echo $secCode; ?>';
    openModal('securityCodeModal');
    closeMenu();
}

function toggleMenu() {
    const sidebar = document.getElementById('sidebar');
    if (window.innerWidth >= 769) { 
        sidebar.classList.toggle('closed'); 
    } else { 
        sidebar.classList.toggle('open'); 
        const bd = document.querySelector('.backdrop'); 
        if(sidebar.classList.contains('open')) bd.classList.add('show'); 
        else bd.classList.remove('show'); 
    }
}

function closeMenu() { 
    if (window.innerWidth < 769) { 
        document.getElementById('sidebar').classList.remove('open'); 
        document.querySelector('.backdrop').classList.remove('show'); 
    } 
}

function openModal(id) { 
    const m = document.getElementById(id); 
    if(id === 'msgMenu') m.style.display = 'flex';
    else m.style.visibility = 'visible';
    
    m.style.display = 'flex'; 
    requestAnimationFrame(() => m.classList.add('active')); 
}

function closeModal(id) { 
    const m = document.getElementById(id); 
    m.classList.remove('active'); 
    setTimeout(() => { 
        m.style.display = 'none'; 
        if(id !== 'msgMenu') m.style.visibility = 'hidden';
    }, 300); 
}

function showToast(msg) { 
    const t = document.getElementById("toast"); 
    t.innerHTML = msg; 
    t.classList.add("show"); 
    setTimeout(() => t.classList.remove("show"), 3000); 
}

function showConfirm(msg, cb) { 
    document.getElementById('confirmMsg').textContent = msg; openModal('modalConfirm');
    const oldBtn = document.getElementById('confirmYes'); const newBtn = oldBtn.cloneNode(true);
    oldBtn.parentNode.replaceChild(newBtn, oldBtn);
    newBtn.onclick = () => { cb(); closeModal('modalConfirm'); }; 
}

function showPrompt(msg, val, cb) { 
    document.getElementById('promptMsg').textContent = msg; const i = document.getElementById('promptInput'); i.value = val || '';
    openModal('modalPrompt'); i.focus();
    const oldBtn = document.getElementById('promptOk'); const newBtn = oldBtn.cloneNode(true);
    oldBtn.parentNode.replaceChild(newBtn, oldBtn);
    newBtn.onclick = () => { if(i.value) cb(i.value); closeModal('modalPrompt'); }; 
}

function performLogout() {
    fetch(`requests.php?logout=1&nonce=${logoutNonce}`)
    .then(() => {
        window.location.href = 'index.php';
    })
    .catch(() => {
        window.location.href = 'index.php';
    });
}

function askLogout() { 
    showConfirm('آیا مطمئن هستید که می‌خواهید خارج شوید؟', performLogout); 
}

function handleBan(message, type) {
    document.getElementById('banMessageDetail').innerHTML = message ? message.replace(/\n/g, '<br>') : 'دسترسی شما به سیستم محدود شده است.';
    
    const modal = document.getElementById('banModal');
    modal.classList.add('active');
    
    const content = modal.querySelector('.ban-modal-content');
    content.style.background = 'linear-gradient(145deg, #7f1d1d, #450a0a)';
    content.style.borderColor = '#ef4444';
    modal.querySelector('.ban-icon').style.filter = 'drop-shadow(0 0 25px rgba(239, 68, 68, 0.8))';
    
    if (type !== 'perm') {
        setTimeout(() => {
            window.location.href = 'requests.php?logout=1&nonce=' + logoutNonce;
        }, 5000);
    }
}

function showNotification(senderId, name, sticker, txt, msgId, roomName) {
    if (!notificationsEnabled) return;
    if (msgId && shownNotificationIds.has(msgId)) return;
    if (msgId) shownNotificationIds.add(msgId);
    
    if(notifQueue.some(n => n.senderId === senderId && n.txt === txt)) return; 
    notifQueue.push({senderId, name, sticker, txt, msgId, roomName});
    processNotifQueue();
}

function processNotifQueue() {
    if (!notificationsEnabled) {
        notifQueue = [];
        isShowingNotif = false;
        document.getElementById('notifPopup').classList.remove('show');
        return;
    }

    if (isShowingNotif || notifQueue.length === 0) return;
    
    const item = notifQueue[0];
    
    if (targetUser && item.senderId == targetUser) {
        notifQueue.shift();
        processNotifQueue();
        return;
    }
    
    isShowingNotif = true;
    notifQueue.shift();
    notifData = item;
    
    const p = document.getElementById('notifPopup');
    document.getElementById('notifAvatar').textContent = item.sticker;
    document.getElementById('notifName').textContent = item.name;
    document.getElementById('notifText').textContent = item.txt;
    p.classList.add('show');
    
    const sound = new Audio('data:audio/wav;base64,UklGRl9vT19XQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU'); 
    sound.play().catch(()=>{});

    setTimeout(() => {
        p.classList.remove('show');
        setTimeout(() => { isShowingNotif = false; notifData = null; processNotifQueue(); }, 500);
    }, 4500);
}

function handleNotifClick() {
    if(notifData) {
        if (notifData.type === 'room_invite') {
            switchRoom(notifData.room, notifData.room, notifData.roomId);
        } else if (notifData.roomName && notifData.roomName !== 'null') {
            switchRoom(notifData.roomName, notifData.roomName, null);
        } else {
            startPrivate(notifData.senderId, notifData.name);
        }
        document.getElementById('notifPopup').classList.remove('show');
        isShowingNotif = false;
        notifData = null;
    }
}

function toggleNotifications(el) {
    notificationsEnabled = el.checked;
    if (!notificationsEnabled) {
        notifQueue = [];
        isShowingNotif = false;
        document.getElementById('notifPopup').classList.remove('show');
    }
    apiCall('chat.php', 'toggle_notifications', {state: notificationsEnabled ? 1 : 0});
}

function apiCall(file, action, data, cb, errCb) {
    const fd = new FormData();
    fd.append('action', action); fd.append('csrf_token', csrfToken);
    if(bwNonce) fd.append('bw_nonce', bwNonce);
    for(let k in data) fd.append(k, data[k]);
    
    fetch(file, {method:'POST', body:fd})
    .then(r => r.text())
    .then(text => {
        if (text.includes('دسترسی شما به دلیل نقض قوانین') || text.trim().startsWith('<div')) {
            handleBan(null, 'perm');
            if(errCb) errCb();
            return;
        }
        try {
            const d = JSON.parse(text);
            if(d.status === 'banned') { handleBan(d.message, d.ban_type || 'temp'); if(errCb) errCb(); }
            else if(d.status === 'error' && d.message === 'کاربر یافت نشد') { location.reload(); if(errCb) errCb(); }
            else if(d.status === 'auth_fail') { location.reload(); if(errCb) errCb(); }
            else if(d.status === 'access_denied') { openModal('accessDeniedModal'); if(errCb) errCb(); }
            else if(d.status === 'success') { if(cb) cb(d); }
            else { 
                if(cb && typeof cb === 'function' && cb.length > 0) cb(d); 
                else { showToast(d.message); if(errCb) errCb(); } 
            }
        } catch(e) {
            if(errCb) errCb();
        }
    }).catch(() => {
        if(errCb) errCb();
    });
}

function formatText(t) {
    if (!t) return '';
    const urlRegex = /(?:https?:\/\/|www\.)?[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[a-zA-Z]{2,}(?:\/[^\s<]*[^.,!?\s<])?/gi;
    return t.replace(urlRegex, function(url) {
        let href = url.match(/^https?:\/\//i) ? url : 'http://' + url;
        return `<a href="${href}" target="_blank" style="color:#818cf8;text-decoration:underline" onclick="event.stopPropagation()">${url}</a>`;
    }).replace(/\n/g, '<br>');
}

function renderMessage(m, isPending=false, isError=false) {
    if (m.message && m.message.startsWith('***') && m.message.endsWith('***')) {
        const text = m.message.replace(/\*\*\*/g, '').trim();
        return `<div class="msg system">${text}</div>`;
    }

    let sName = m.sender_name || '...';
    let sSticker = m.sender_sticker || '👤';
    const adminUser = '<?php echo $admin_username; ?>';
    const adminDisplay = '<?php echo $admin_display_name; ?>';
    
    if (sName === adminUser) {
        sName = adminDisplay + ' <svg style="width:12px;height:12px;vertical-align:middle;fill:#3b82f6" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>';
    }

    const isMe = m.sender_id == currentUser || isPending;
    
    let displaySenderName = isMe ? 'شما' : sName;

    let cnt = '';
    
    if(m.msg_type === 'text') {
        cnt = `<div class="text-glass-box">${formatText(m.message || '')}${m.is_edited==1 ? ' <small style="opacity:0.5">(ویرایش)</small>' : ''}</div>`;
    } else if(m.msg_type === 'voice') {
        const src = isPending ? URL.createObjectURL(m.blob) : `voice.php?token=${m.file_token}`;
        cnt = `<audio controls preload="none" src="${src}"></audio>`;
    } else if(['jpg','png','gif','webp'].includes(m.file_name?.split('.').pop())) {
        const src = isPending ? '' : `file.php?token=${m.file_token}`;
        cnt = `<div class="media-container"><img src="${src}" class="chat-media" onclick="window.open(this.src)"></div>`;
        if(m.message) cnt += `<div class="text-glass-box chat-text">${formatText(m.message)}</div>`;
    } else if(['mp4','webm','mov'].includes(m.file_name?.split('.').pop())) {
        const src = isPending ? '' : `file.php?token=${m.file_token}`;
        cnt = `<div class="media-container"><video src="${src}" class="chat-media" controls></video></div>`;
        if(m.message) cnt += `<div class="text-glass-box chat-text">${formatText(m.message)}</div>`;
    } else {
        const dlLink = isPending ? '#' : `file.php?token=${m.file_token}&dl=1`;
        cnt = `<div class="file-box">
                <div class="file-icon">📄</div>
                <div class="file-info"><span class="file-name">${m.file_name}</span></div>
                ${!isPending ? `<a href="${dlLink}" class="download-btn" download target="_blank">⬇</a>` : ''}
               </div>`;
        if(m.message) cnt += `<div class="text-glass-box chat-text">${formatText(m.message)}</div>`;
    }
    
    let rep = '';
    if(m.reply_to_id) {
        let repTxt = m.reply_message;
        if ((!repTxt || repTxt === '...') && m.reply_file_name) {
            repTxt = '[فایل پیوست]';
        } else if (!repTxt && !m.reply_file_name) {
            repTxt = '<span style="font-style:italic;opacity:0.6;">پیام حذف شده</span>';
        }
        
        let rSender = m.reply_sender;
        if (!rSender || rSender === '...') {
            if (msgCache[m.reply_to_id]) {
                rSender = msgCache[m.reply_to_id].name;
            } else if (m.reply_sender_username) {
                rSender = m.reply_sender_username;
            } else {
                rSender = '...';
            }
        }
        
        if (rSender === adminUser) {
             rSender = adminDisplay;
        }
        
        rep = `<div class="reply-content" onclick="event.stopPropagation(); scrollToMsg(${m.reply_to_id})">
            <span class="reply-sender">${rSender}</span>
            <span class="reply-text">${repTxt}</span>
        </div>`;
    }

    const safeMsg = m.msg_type === 'text' ? (m.message || '').replace(/'/g, "&apos;").replace(/"/g, "&quot;").replace(/\n/g, "\\n") : '';
    const pendingClass = isPending ? 'pending' : '';
    const errorClass = isError ? 'error' : '';
    const retryBtn = isError ? `<div class="retry-btn" onclick="retrySend(${m.tempId})">تلاش مجدد ↻</div>` : '';

    return `<div class="msg ${isMe?'me':'other'} ${pendingClass} ${errorClass}" id="msg-${m.id || m.tempId}" onclick="handleMsgClick(${m.id}, ${isMe}, '${safeMsg}', '${m.sender_name}')" oncontextmenu="handleMsgCtx(event, ${m.id}, ${isMe}, '${safeMsg}', '${m.sender_name}')">
        ${rep} 
        <div class="sender" style="align-items:center;">${sSticker} ${displaySenderName} <span style="font-size:10px; margin-right:auto; background:rgba(0,0,0,0.2); padding:3px 8px; border-radius:12px; color:#e2e8f0; border: 1px solid rgba(255,255,255,0.05); box-shadow:inset 0 1px 2px rgba(0,0,0,0.2); letter-spacing:0.5px;">${m.created_at ? m.created_at.substr(11,5) : '...'}</span></div> 
        ${cnt}
        ${retryBtn}
    </div>`;
}

function scrollToMsg(id) {
    const el = document.getElementById('msg-' + id);
    if(el) {
        el.scrollIntoView({behavior: 'smooth', block: 'center'});
        el.classList.remove('highlight-target');
        setTimeout(() => {
            el.classList.add('highlight-target');
            setTimeout(() => el.classList.remove('highlight-target'), 2500);
        }, 800);
    } else {
        showToast('پیام مرجع در دسترس نیست (شاید قدیمی باشد)');
    }
}

function checkAndShowSecurityCode() {
    const sc = localStorage.getItem('newSecurityCode');
    if(sc) {
        document.getElementById('newSecurityCodeDisplay').textContent = sc;
        openModal('securityCodeModal');
        localStorage.removeItem('newSecurityCode'); 
    } else {
        localStorage.setItem('welcomeShown','true');
    }
}

function updateScrollBadge() {
    const badge = document.getElementById('scrollUnreadBadge');
    if (newMessagesSinceScroll > 0) {
        badge.style.display = 'flex';
        badge.textContent = newMessagesSinceScroll > 99 ? '+99' : newMessagesSinceScroll;
    } else {
        badge.style.display = 'none';
    }
}

function fetchMessages() {
    apiCall('chat.php', 'fetch', {room:currentRoom, target_user:targetUser}, d=>{
        currentUser = d.current_user_id; isAdmin = d.is_admin;
        if(d.bw_nonce) bwNonce = d.bw_nonce;
        document.getElementById('adminPanelBtn').style.display = isAdmin ? 'flex' : 'none';
        
        const me = d.users.find(u => u.id == currentUser);
        if(me) currentUsername = me.username;
        
        if (d.user_settings && typeof d.user_settings.notifications !== 'undefined') {
            notificationsEnabled = (d.user_settings.notifications == 1);
            document.getElementById('notifToggle').checked = notificationsEnabled;
        }

        if(!localStorage.getItem('welcomeShown') && !isAdmin) {
            openModal('welcomeModal');
        } else if (localStorage.getItem('newSecurityCode')) {
            checkAndShowSecurityCode();
        }

        if(d.notifications && d.notifications.length > 0) {
            d.notifications.forEach(n => {
                showNotification(n.sender_id, n.name, n.sticker, n.txt, n.msg_id, n.room_name);
                if (n.room_name && n.room_name !== currentRoom && targetUser == null) {
                    roomUnreadLocal[n.room_name] = (roomUnreadLocal[n.room_name] || 0) + 1;
                }
            });
        }

        const list = document.getElementById('sidebarList');
        const q = document.querySelector('.search-input').value.toLowerCase();
        
        let html = '<div class="list-header">اتاق‌ها</div>';
        
        let publicRoomId = null;
        if(d.rooms) { 
            allRoomsCache = d.rooms;
            d.rooms.forEach(r => { if(r.name === 'گفتگوی عمومی' || r.name === 'public') publicRoomId = r.id; }); 
        }
        if (currentRoom === 'گفتگوی عمومی' && !currentRoomId && publicRoomId) currentRoomId = publicRoomId;

        let pubCount = roomUnreadLocal['گفتگوی عمومی'] || 0;
        let publicRoomUnread = '';
        if(pubCount > 0 && currentRoom !== 'گفتگوی عمومی') {
            let pDispCount = pubCount > 99 ? '+99' : pubCount;
            publicRoomUnread = `<span class="unread-badge">${pDispCount}</span>`;
        }

        const isPublicActive = currentRoom === 'گفتگوی عمومی' && !targetUser ? 'active' : '';
        if('گفتگوی عمومی'.includes(q) || 'public'.includes(q)) {
            html += `<div class="item ${isPublicActive}" onclick="switchRoom('گفتگوی عمومی', 'گفتگوی عمومی', ${publicRoomId})">
                <div class="item-row" style="align-items: center;">
                    <div style="display:flex; flex-direction:column; min-width:0; flex:1;">
                        <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-weight:bold;">🌐 گفتگوی عمومی</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
                        ${publicRoomUnread}
                    </div>
                </div>
            </div>`;
        }

        if (d.my_invites) {
            d.my_invites.forEach(invRoom => {
                if (!myInvites.includes(invRoom)) {
                    myInvites.push(invRoom);
                    let r = d.rooms.find(x => x.name === invRoom);
                    if (r) {
                        showNotification(0, "دعوت سیستمی", "📩", `شما به اتاق "${invRoom}" دعوت شدید`, null, invRoom);
                        notifData = { type: 'room_invite', room: invRoom, roomId: r.id }; 
                        switchRoom(invRoom, invRoom, r.id); 
                    }
                }
            });
        }

        if (d.rooms) {
            d.rooms.sort((a, b) => {
                if (a.type === 'public' && b.type !== 'public') return -1;
                if (a.type !== 'public' && b.type === 'public') return 1;
                return 0;
            });
            
            d.rooms.forEach(r=>{
                if(r.name === 'گفتگوی عمومی' || r.name === 'public') return;
                if(!r.name.toLowerCase().includes(q)) return;
                
                const isActive = currentRoom === r.name && !targetUser ? 'active' : '';
                const icon = r.type === 'private' ? '🔒' : '💬';
                
                let roomUnreadBadge = '';
                let roomCount = roomUnreadLocal[r.name] || 0;
                if(roomCount > 0 && r.name !== currentRoom) {
                     let dispCount = roomCount > 99 ? '+99' : roomCount;
                     roomUnreadBadge = `<span class="unread-badge">${dispCount}</span>`;
                }
                
                let codeDisplay = '';
                if (r.type === 'private' && r.creator === currentUsername && r.invite_code) {
                    codeDisplay = `<span class="room-code-badge" onclick="event.stopPropagation(); copyText('${r.invite_code}')">${r.invite_code}</span>`;
                }
                
                let creatorHtml = '';
                if(r.type === 'private' && r.creator) {
                    creatorHtml = `<span style="font-size:10px;opacity:0.6;margin-top:2px;display:block;">👤 سازنده: ${r.creator}</span>`;
                }

                html += `<div class="item ${isActive}" onclick="switchRoom('${r.name}', '${r.name}', ${r.id})">
                    <div class="item-row" style="align-items: center;">
                        <div style="display:flex; flex-direction:column; min-width:0; flex:1;">
                            <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-weight:bold;">${icon} ${r.name}</span>
                            ${creatorHtml}
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
                            ${codeDisplay}
                            ${roomUnreadBadge}
                        </div>
                    </div>
                </div>`;
            });
        }

        if(foundRoom && !html.includes(foundRoom.name)) {
             html += `<div class="found-room-card" onclick="joinRoomByCode('${foundCode}')">
                <div class="found-room-content">
                    <div class="found-room-info">
                        <span class="found-room-name">${foundRoom.name}</span>
                        <span style="font-size:10px; color:#a7f3d0">اتاق خصوصی</span>
                    </div>
                    <span class="found-room-action">عضویت +</span>
                </div>
             </div>`;
        }

        html += '<div class="list-header">کاربران آنلاین</div>';
        allUsersCache = d.users;
        
        const adminUser = '<?php echo $admin_username; ?>';
        d.users.sort((a, b) => {
            if (a.username === adminUser) return -1;
            if (b.username === adminUser) return 1;
            return (b.is_online - a.is_online);
        });
        
        d.users.forEach(u=>{
            if(u.id == currentUser) return;
            if(!u.username.toLowerCase().includes(q)) return;
            
            const currentUnread = d.unread[u.id] || 0;
            const prevUnread = lastUnreadCounts[u.id] || 0;
            
            if (currentUnread > prevUnread && targetUser != u.id) {
                showNotification(u.id, u.username, u.sticker, "پیام جدید", null, null);
            }
            lastUnreadCounts[u.id] = currentUnread;

            const isActive = targetUser == u.id ? 'active' : '';
            let dispUnread = currentUnread > 99 ? '+99' : currentUnread;
            const unread = (currentUnread && targetUser != u.id) ? `<span class="unread-badge">${dispUnread}</span>` : '';
            
            let statusHtml = '';
            if(u.is_online == 1) {
                statusHtml = '<div style="font-size:10px;margin-top:2px;" class="status-online">آنلاین</div>';
            } else {
                let timeStr = u.last_activity ? u.last_activity.split(' ')[1].substr(0, 5) : '';
                statusHtml = `<div style="font-size:10px;margin-top:2px;" class="status-offline">آخرین بازدید: ${timeStr}</div>`;
            }
            
            let displayUsername = u.username;
            let checkBadge = '';
            
            if (u.username === adminUser) {
                displayUsername = '<?php echo $admin_display_name; ?>';
                checkBadge = '<svg style="width:14px;height:14px;vertical-align:middle;margin-right:4px;fill:#3b82f6;" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>';
            }

            html += `<div class="item ${isActive}" onclick="startPrivate(${u.id}, '${u.username}')" oncontextmenu="handleUserCtx(event, ${u.id}, '${u.username}')">
                <div class="item-row" style="align-items: center;">
                    <div class="user-info-col" style="min-width:0; flex:1;">
                        <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis; font-weight:bold;">${u.sticker} ${displayUsername} ${checkBadge}</span>
                        ${statusHtml}
                    </div>
                    <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
                        ${unread}
                    </div>
                </div>
            </div>`;
        });
        
        if(list.innerHTML !== html) list.innerHTML = html;

        let vLock = false;
        let fLock = false;
        
        if (d.settings) {
            vLock = (d.settings.lock_voice == 1);
            fLock = (d.settings.lock_upload == 1);
        }
        else {
            if (typeof d.lock_voice !== 'undefined') vLock = (d.lock_voice == 1);
            if (typeof d.lock_upload !== 'undefined') fLock = (d.lock_upload == 1);
        }

        const attachBtn = document.getElementById('attachBtn');
        const micBtn = document.getElementById('micBtn');
        
        if (attachBtn) attachBtn.style.display = fLock ? 'none' : 'flex';
        if (micBtn) micBtn.style.display = vLock ? 'none' : 'flex';

        if(d.global_alert && d.global_alert.message) {
            const lastSeen = localStorage.getItem('last_seen_alert_id');
            const alertId = d.global_alert.id || d.global_alert.message; 
            
            if(lastSeen != alertId) {
                document.getElementById('globalAlertText').innerHTML = d.global_alert.message.replace(/\n/g, '<br>');
                openModal('globalAlertModal');
                localStorage.setItem('last_seen_alert_id', alertId);
            }
        }

        const box = document.getElementById('messagesBox');
        const contentBox = document.getElementById('msgsContent');
        const loadBtn = document.getElementById('loadMoreBtn');
        
        const oldScrollHeight = box.scrollHeight;
        const oldScrollTop = box.scrollTop;
        const scrollBottom = oldScrollHeight - oldScrollTop - box.clientHeight;
        const wasAtBottom = scrollBottom < 50; 
        
        const isFirstLoad = contentBox.innerHTML.trim() === '';

        let typingText = '';
        if(d.typing_users && d.typing_users.length > 0) {
            typingText = d.typing_users.join(', ') + ' در حال نوشتن...';
        }
        const typingEl = document.getElementById('typingStatus');
        if(typingText) {
            typingEl.textContent = typingText;
            typingEl.style.display = 'block';
        } else {
            typingEl.style.display = 'none';
        }

        if(d.messages && d.messages.length > 0) {
            let msgHtml = '';
            let firstId = d.messages[0].id;
            let incomingNew = 0;
            
            if (!oldestMsgId || firstId < oldestMsgId) oldestMsgId = firstId;

            d.messages.forEach(m=>{
                if (m.id > lastMsgId) {
                    if (m.sender_id != currentUser) incomingNew++;
                    lastMsgId = m.id;
                }
                msgCache[m.id] = { name: m.sender_name, text: m.message };
                msgHtml += renderMessage(m);
            });
            
            const pendingMsgs = document.querySelectorAll('.msg.pending');
            let pendingHtml = '';
            pendingMsgs.forEach(el => pendingHtml += el.outerHTML);

            if(contentBox.innerHTML.replace(pendingHtml, '') !== (loadedHistoryHtml + msgHtml)) {
                const oldBehavior = box.style.scrollBehavior;
                if (isFirstLoad) box.style.scrollBehavior = 'auto';

                contentBox.innerHTML = loadedHistoryHtml + msgHtml + pendingHtml;
                void box.offsetHeight; 

                if (isFirstLoad || wasAtBottom) {
                    box.scrollTop = box.scrollHeight;
                    newMessagesSinceScroll = 0;
                    updateScrollBadge();
                } else {
                    box.scrollTop = box.scrollHeight - (oldScrollHeight - oldScrollTop);
                    if (incomingNew > 0) {
                        newMessagesSinceScroll += incomingNew;
                        updateScrollBadge();
                        document.querySelector('.scroll-btn.down').classList.add('visible');
                    }
                }
                
                if (isFirstLoad) {
                    requestAnimationFrame(() => {
                        box.style.scrollBehavior = 'smooth';
                    });
                }
            }
            
            if (d.messages.length >= 20) {
                loadBtn.style.display = 'block';
            } else {
                loadBtn.style.display = 'none';
            }
            
        } else {
            let pendingHtml = '';
            document.querySelectorAll('.msg.pending').forEach(el => pendingHtml += el.outerHTML);
            if (!pendingHtml) {
                contentBox.innerHTML = '<div style="text-align:center;margin-top:50px;opacity:0.5;font-size:12px">پیامی نیست</div>';
            } else {
                contentBox.innerHTML = pendingHtml;
            }
            loadBtn.style.display = 'none';
            loadedHistoryHtml = '';
        }
    });
}

function scrollToTop() {
    document.getElementById('messagesBox').scrollTo({ top: 0, behavior: 'smooth' });
}

function scrollToBottom() {
    const box = document.getElementById('messagesBox');
    box.scrollTo({ top: box.scrollHeight, behavior: 'smooth' });
    newMessagesSinceScroll = 0;
    updateScrollBadge();
}

let scrollTimeout;
document.getElementById('messagesBox').addEventListener('scroll', function() {
    const box = this;
    const scrollTop = box.scrollTop;
    const scrollHeight = box.scrollHeight;
    const clientHeight = box.clientHeight;
    
    const upBtn = document.querySelector('.scroll-btn.up');
    const downBtn = document.querySelector('.scroll-btn.down');
    
    if (scrollTop > 300) {
        upBtn.classList.add('visible');
    } else {
        upBtn.classList.remove('visible');
    }

    if (scrollHeight - scrollTop - clientHeight > 50) {
        downBtn.classList.add('visible');
    } else {
        downBtn.classList.remove('visible');
        newMessagesSinceScroll = 0;
        updateScrollBadge();
    }

    if (scrollTop <= 10 && !isLoadingMore && document.getElementById('loadMoreBtn').style.display !== 'none') {
        loadMoreMessages();
    }
    
    clearTimeout(scrollTimeout);
    scrollTimeout = setTimeout(() => {
        upBtn.classList.remove('visible');
        if (newMessagesSinceScroll === 0) {
            downBtn.classList.remove('visible');
        }
    }, 2500); 
});

function loadMoreMessages() {
    if(!oldestMsgId || isLoadingMore) return;
    isLoadingMore = true;

    const overlay = document.getElementById('historyOverlay');
    overlay.classList.add('active');
    
    const btn = document.getElementById('loadMoreBtn');

    apiCall('chat.php', 'fetch_history', {
        room: currentRoom, 
        target_user: targetUser,
        before_id: oldestMsgId
    }, d => {
        if(d.status === 'success' && d.messages && d.messages.length > 0) {
            let html = '';
            d.messages.forEach(m => {
                msgCache[m.id] = { name: m.sender_name, text: m.message };
                html += renderMessage(m);
            });
            
            const box = document.getElementById('messagesBox');
            const contentBox = document.getElementById('msgsContent');
            
            const oldScrollHeight = box.scrollHeight;
            const oldScrollTop = box.scrollTop;
            
            const originalScrollBehavior = window.getComputedStyle(box).scrollBehavior;
            box.style.scrollBehavior = 'auto';
            
            loadedHistoryHtml = html + loadedHistoryHtml;
            contentBox.insertAdjacentHTML('afterbegin', html);
            oldestMsgId = d.messages[0].id;
            
            void box.offsetHeight; 
            
            const newScrollHeight = box.scrollHeight;
            box.scrollTop = oldScrollTop + (newScrollHeight - oldScrollHeight);
            
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    box.style.scrollBehavior = originalScrollBehavior;
                });
            });
            
            if (d.messages.length >= 20) {
                btn.style.display = 'block';
            } else {
                btn.style.display = 'none';
                setTimeout(() => { showToast('همه پیام‌های قبلی بارگذاری شدند'); }, 700);
            }
        } else {
            btn.style.display = 'none';
            setTimeout(() => { showToast('پیام قدیمی‌تری برای نمایش وجود ندارد'); }, 700);
        }
        
        setTimeout(() => {
            overlay.classList.remove('active');
            setTimeout(() => {
                isLoadingMore = false;
            }, 300);
        }, 600); 
    }, () => {
        showToast('خطا در دریافت اطلاعات');
        setTimeout(() => {
            overlay.classList.remove('active');
            isLoadingMore = false;
            btn.style.display = 'block'; 
        }, 600);
    });
}

function switchRoom(r, t, id){ 
    currentRoom=r; currentRoomId=id; targetUser=null; 
    document.getElementById('chatTitle').textContent=t; 
    document.getElementById('chatStatus').textContent='اتاق عمومی'; 
    lastMsgId = 0; oldestMsgId = null;
    loadedHistoryHtml = '';
    roomUnreadLocal[r] = 0; 
    document.getElementById('msgsContent').innerHTML = '';
    document.getElementById('loadMoreBtn').style.display = 'none';
    fetchMessages(); 
    closeMenu(); 
}

function startPrivate(u, n){ 
    targetUser=u; currentRoom=null; 
    document.getElementById('chatTitle').textContent=n; 
    document.getElementById('chatStatus').textContent='گفتگوی خصوصی'; 
    lastMsgId = 0; oldestMsgId = null;
    loadedHistoryHtml = '';
    document.getElementById('msgsContent').innerHTML = '';
    document.getElementById('loadMoreBtn').style.display = 'none';
    fetchMessages(); 
    closeMenu(); 
}

function handleUserCtx(e, id, name){ e.preventDefault(); ctxId=id; ctxName=name; showCtx(e); }
function showCtx(e){ const m=document.getElementById('ctxMenu'); let x=e.pageX, y=e.pageY; if(x+160>window.innerWidth)x-=160; m.style.left=x+'px'; m.style.top=y+'px'; m.style.display='block'; document.getElementById('ctxBan').style.display=isAdmin?'flex':'none'; document.getElementById('ctxInvite').style.display=isAdmin?'flex':'none'; }
function hideCtx(){ document.getElementById('ctxMenu').style.display='none'; document.getElementById('msgCtxMenu').style.display='none'; }
function handleCtx(act){ hideCtx(); if(act==='private') startPrivate(ctxId, ctxName); if(act==='ban') showConfirm('مسدود شود؟', ()=>apiCall('admin.php','ban_user',{user_id:ctxId},()=>showToast('انجام شد'))); if(act==='invite') openInviteModal(); }

function handleMsgClick(id, isMe, text, senderName) {
    if(window.innerWidth < 769) {
        openMsgMenu(id, isMe, text, senderName);
    }
}

function handleMsgCtx(e, id, isMe, text, senderName) {
    e.preventDefault();
    hideCtx();
    selectedMsgId = id;
    selectedMsgText = text;
    selectedMsgSender = senderName;

    const m = document.getElementById('msgCtxMenu');
    let x = e.pageX, y = e.pageY;
    if(x + 160 > window.innerWidth) x -= 160;
    if(y + 150 > window.innerHeight) y -= 150;
    m.style.left = x + 'px';
    m.style.top = y + 'px';
    m.style.display = 'block';

    document.getElementById('ctxEditMsg').style.display = (isMe && text) ? 'flex' : 'none';
    document.getElementById('ctxDelMsg').style.display = (isMe || isAdmin) ? 'flex' : 'none';
}

function handleMsgCtxAction(act) {
    hideCtx();
    if(act === 'reply') setReply(selectedMsgId, selectedMsgSender, selectedMsgText);
    if(act === 'edit') editMsg(selectedMsgId, selectedMsgText);
    if(act === 'delete') delMsg(selectedMsgId);
    if(act === 'copy') copyMsg(selectedMsgText);
}

function openMsgMenu(id, isMe, text, senderName) {
    if(!id) return; 
    selectedMsgId = id;
    selectedMsgText = text;
    selectedMsgSender = senderName;
    
    const grid = document.getElementById('msgActionsGrid');
    let btns = `
        <div class="action-item" onclick="setReply(${id}, '${senderName}', '${text ? text.replace(/'/g, "\\'") : ''}'); closeMsgMenu()">
            <span class="action-icon">↩</span> پاسخ
        </div>
    `;
    
    if(text) {
        btns += `
            <div class="action-item" onclick="copyMsg('${text.replace(/'/g, "\\'")}'); closeMsgMenu()">
                <span class="action-icon">📋</span> کپی
            </div>
        `;
    }
    
    if(isMe && text) {
        btns += `
            <div class="action-item" onclick="editMsg(${id}, '${text.replace(/'/g, "\\'") }'); closeMsgMenu()">
                <span class="action-icon">✎</span> ویرایش
            </div>
        `;
    }
    
    if(isMe || isAdmin) {
        btns += `
            <div class="action-item" onclick="delMsg(${id}); closeMsgMenu()" style="color:#f87171; border-color:rgba(239,68,68,0.3)">
                <span class="action-icon">🗑</span> حذف
            </div>
        `;
    }
    
    grid.innerHTML = btns;
    document.getElementById('msgMenuTitle').textContent = `گزینه‌های پیام ${senderName}`;
    
    openModal('msgMenu');
}

function closeMsgMenu() {
    closeModal('msgMenu');
}

function copyMsg(text) {
    if(!text) return;
    const tempInput = document.createElement("input");
    tempInput.style = "position: absolute; left: -1000px; top: -1000px";
    tempInput.value = text;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand("copy");
    document.body.removeChild(tempInput);
    showToast('متن کپی شد');
}

function copyNewSecurityCode() {
    const code = document.getElementById('newSecurityCodeDisplay').textContent;
    if(code) {
        const tempInput = document.createElement("input");
        tempInput.style = "position: absolute; left: -1000px; top: -1000px";
        tempInput.value = code;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand("copy");
        document.body.removeChild(tempInput);
        showToast('کد با موفقیت کپی شد! در جای امن نگه‌دارید.');
    }
}

let pendingMessages = [];

function sendMessage(blob, isV, retryId = null){
    const txt = document.getElementById('msgInput');
    const file = document.getElementById('fileInput');
    
    if (!blob && file.files.length > 0 && !retryId) {
        const fileList = Array.from(file.files);
        fileList.forEach(f => {
            if (f.size > MAX_FILE_SIZE) {
                showToast(`حجم فایل ${f.name} بیشتر از ۵ مگابایت است`);
                return;
            }
            uploadQueue.push(f);
        });
        
        processUploadQueue();
        document.getElementById('fileInput').value = '';
        cancelFile();
        return;
    }

    const msgVal = txt.value.trim();
    const currentReplyId = replyToId;
    const currentReplyName = replyName;
    const currentReplyText = replyContent;

    let currentBlob = blob;
    let currentFile = file.files.length ? file.files[0] : null;
    let currentMsg = msgVal ? escapeHTML(msgVal) : ''; 

    if (retryId) {
        const pObj = pendingMessages.find(p => p.tempId === retryId);
        if(pObj) {
            currentBlob = pObj.blob;
            currentFile = pObj.file;
            currentMsg = pObj.msg;
        }
    } else {
        if(!blob && !file.files.length && !currentMsg) return;
    }

    const tempId = retryId || Date.now();
    const tempMsgObj = {
        id: null, tempId: tempId, sender_id: currentUser, sender_name: 'شما', sender_sticker: '👤', created_at: 'در حال ارسال...',
        message: currentMsg, msg_type: isV ? 'voice' : (currentFile ? 'file' : 'text'),
        file_name: currentFile ? currentFile.name : (isV ? 'voice.webm' : null),
        blob: currentBlob || currentFile,
        reply_to_id: currentReplyId, 
        reply_sender: currentReplyName || '...',
        reply_message: currentReplyId ? currentReplyText : null
    };

    if (!retryId) {
        pendingMessages.push({ tempId, blob: currentBlob, file: currentFile, msg: currentMsg, isV });
        const contentBox = document.getElementById('msgsContent');
        const box = document.getElementById('messagesBox');
        
        contentBox.insertAdjacentHTML('beforeend', renderMessage(tempMsgObj, true));
        box.scrollTop = box.scrollHeight;
        
        cancelReply(); cancelFile(); txt.value='';
    } else {
        const el = document.getElementById(`msg-${tempId}`);
        if(el) { el.classList.remove('error'); el.querySelector('.retry-btn')?.remove(); }
    }
    
    const fd = new FormData(); 
    fd.append('action','send'); 
    fd.append('csrf_token', csrfToken); 
    if(bwNonce) fd.append('bw_nonce', bwNonce);
    
    if(targetUser) {
        fd.append('receiver_id', targetUser);
        fd.append('room', 'null');
    } else {
        fd.append('room', currentRoom || 'گفتگوی عمومی');
        if(currentRoomId) fd.append('room_id', currentRoomId);
    }

    if(currentReplyId) fd.append('reply_to', currentReplyId);
    
    if(currentBlob) { 
        fd.append('file', currentBlob, 'voice.webm'); 
        fd.append('is_voice','true'); 
    } else {
        if(currentFile) fd.append('file', currentFile);
        if(currentMsg) fd.append('message', currentMsg);
    }
    
    fetch('chat.php',{method:'POST',body:fd})
        .then(r=>r.json())
        .then(d=>{
            if(d.status==='success') {
                const el = document.getElementById(`msg-${tempId}`);
                if(el) {
                    el.id = `msg-${d.new_msg_id}`;
                    el.classList.remove('pending');
                    el.classList.remove('error');
                    
                    const safeMsg = (currentMsg || '').replace(/'/g, "&apos;").replace(/"/g, "&quot;").replace(/\n/g, "\\n");
                    el.setAttribute('onclick', `handleMsgClick(${d.new_msg_id}, true, '${safeMsg}', 'شما')`);
                    el.setAttribute('oncontextmenu', `handleMsgCtx(event, ${d.new_msg_id}, true, '${safeMsg}', 'شما')`);
                    
                    msgCache[d.new_msg_id] = { name: 'شما', text: currentMsg };
                }
                pendingMessages = pendingMessages.filter(p => p.tempId !== tempId);
            } else {
                markAsError(tempId);
                showToast(d.message);
            }
        })
        .catch(()=>{
            markAsError(tempId);
            showToast('خطای شبکه');
        });
}

function processUploadQueue() {
    if (isUploading || uploadQueue.length === 0) return;
    
    isUploading = true;
    const f = uploadQueue.shift();
    
    const tId = Date.now();
    const tObj = {
        id: null, tempId: tId, sender_id: currentUser, sender_name: 'شما', sender_sticker: '👤', created_at: 'در حال ارسال...',
        message: '', msg_type: 'file', file_name: f.name, blob: f,
        reply_to_id: null, reply_sender: '...', reply_message: null
    };
    
    pendingMessages.push({ tempId: tId, blob: null, file: f, msg: '', isV: false });
    document.getElementById('msgsContent').insertAdjacentHTML('beforeend', renderMessage(tObj, true));
    document.getElementById('messagesBox').scrollTop = document.getElementById('messagesBox').scrollHeight;

    const fd = new FormData();
    fd.append('action', 'send');
    fd.append('csrf_token', csrfToken);
    if(bwNonce) fd.append('bw_nonce', bwNonce);
    
    if(targetUser) {
        fd.append('receiver_id', targetUser);
        fd.append('room', 'null');
    } else {
        fd.append('room', currentRoom || 'گفتگوی عمومی');
        if(currentRoomId) fd.append('room_id', currentRoomId);
    }
    fd.append('file', f);

    fetch('chat.php', {method: 'POST', body: fd})
        .then(r => r.json())
        .then(d => {
            const el = document.getElementById(`msg-${tId}`);
            if (d.status === 'success') {
                if(el) {
                    el.id = `msg-${d.new_msg_id}`;
                    el.classList.remove('pending', 'error');
                    el.setAttribute('onclick', `handleMsgClick(${d.new_msg_id}, true, '', 'شما')`);
                }
                pendingMessages = pendingMessages.filter(p => p.tempId !== tId);
            } else {
                markAsError(tId);
                showToast(d.message);
            }
        })
        .catch(() => markAsError(tId))
        .finally(() => {
            isUploading = false;
            setTimeout(processUploadQueue, 500); 
        });
}

function markAsError(tempId) {
    const el = document.getElementById(`msg-${tempId}`);
    if(el) {
        el.classList.add('error');
        if(!el.querySelector('.retry-btn')) {
            el.innerHTML += `<div class="retry-btn" onclick="retrySend(${tempId})">تلاش مجدد ↻</div>`;
        }
    }
}

function retrySend(tempId) {
    const pObj = pendingMessages.find(p => p.tempId === tempId);
    if(pObj) sendMessage(null, pObj.isV, tempId);
}

document.getElementById('msgInput').addEventListener('keydown', function(e) {
    if(e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
    
    clearTimeout(typingTimeout);
    apiCall('chat.php', 'update_typing', { room: currentRoom || 'null', target_user: targetUser || 0 });
    
    typingTimeout = setTimeout(() => {
    }, 2000);
});

function setReply(id, name, text){ 
    replyToId = id; 
    replyContent = text || '...';
    replyName = name;
    document.getElementById('replyBar').style.display='flex'; 
    document.getElementById('replyText').textContent = name + ': ' + (text ? text.substr(0,30) : '[فایل]'); 
    document.getElementById('msgInput').focus(); 
}

function cancelReply(){ replyToId=null; replyContent=null; replyName=null; document.getElementById('replyBar').style.display='none'; }
function cancelFile(){ document.getElementById('fileInput').value=''; document.getElementById('filePreview').style.display='none'; }
document.getElementById('fileInput').onchange = function(){ 
    if(this.files.length > 0){ 
        document.getElementById('filePreview').style.display='flex'; 
        if(this.files.length > 1) {
             document.getElementById('fileName').textContent = this.files.length + ' فایل انتخاب شد';
        } else {
             document.getElementById('fileName').textContent = this.files[0].name; 
        }
    } 
};

function delMsg(id){ 
    showConfirm('آیا این پیام حذف شود؟', () => {
        const el = document.getElementById('msg-' + id);
        if(el) el.remove();
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = loadedHistoryHtml;
        const histEl = tempDiv.querySelector('#msg-' + id);
        if(histEl) {
            histEl.remove();
            loadedHistoryHtml = tempDiv.innerHTML;
        }
        const contentBox = document.getElementById('msgsContent');
        if (!contentBox.querySelector('.msg') && !document.querySelector('.msg.pending')) {
            contentBox.innerHTML = '<div style="text-align:center;margin-top:50px;opacity:0.5;font-size:12px">پیامی نیست</div>';
        }
        apiCall('chat.php', 'delete_msg', {id: id}, fetchMessages);
    }); 
}

function editMsg(id, txt){ showPrompt('ویرایش پیام:', txt, n=>{ 
    if(n!==txt) {
        apiCall('chat.php','edit_msg',{id:id, text:n}, d => {
            if(d.status === 'success') {
                showToast('پیام ویرایش شد');
                const safeMsg = n.replace(/'/g, "&apos;").replace(/"/g, "&quot;").replace(/\n/g, "\\n");
                const formattedMsg = formatText(n) + ' <small style="opacity:0.5">(ویرایش)</small>';
                
                const msgEl = document.getElementById('msg-' + id);
                if(msgEl) {
                    let domTb = msgEl.querySelector('.text-glass-box');
                    if (domTb) {
                        domTb.innerHTML = formattedMsg;
                    } else {
                        msgEl.innerHTML += `<div class="text-glass-box chat-text">${formattedMsg}</div>`;
                    }
                    msgEl.setAttribute('onclick', `handleMsgClick(${id}, true, '${safeMsg}', 'شما')`);
                    msgEl.setAttribute('oncontextmenu', `handleMsgCtx(event, ${id}, true, '${safeMsg}', 'شما')`);
                    if(msgCache[id]) msgCache[id].text = n;
                }

                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = loadedHistoryHtml;
                const histEl = tempDiv.querySelector('#msg-' + id);
                if(histEl) {
                    let histTb = histEl.querySelector('.text-glass-box');
                    if(histTb) {
                        histTb.innerHTML = formattedMsg;
                    } else {
                        histEl.innerHTML += `<div class="text-glass-box chat-text">${formattedMsg}</div>`;
                    }
                    histEl.setAttribute('onclick', `handleMsgClick(${id}, true, '${safeMsg}', 'شما')`);
                    histEl.setAttribute('oncontextmenu', `handleMsgCtx(event, ${id}, true, '${safeMsg}', 'شما')`);
                    loadedHistoryHtml = tempDiv.innerHTML;
                }
                
                fetchMessages();
            } else {
                showToast(d.message || 'خطا در ویرایش پیام');
            }
        });
    }
}); }

function askDeleteAll(){
    showConfirm('آیا از حذف تمام پیام‌ها مطمئن هستید؟', function(){
        setTimeout(function(){
            showConfirm('هشدار جدی: این عملیات غیرقابل بازگشت است. ادامه می‌دهید؟', function(){
                document.querySelectorAll('.msg.me').forEach(el => el.remove());
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = loadedHistoryHtml;
                tempDiv.querySelectorAll('.msg.me').forEach(el => el.remove());
                loadedHistoryHtml = tempDiv.innerHTML;
                
                const contentBox = document.getElementById('msgsContent');
                if (!contentBox.querySelector('.msg') && !document.querySelector('.msg.pending')) {
                    contentBox.innerHTML = '<div style="text-align:center;margin-top:50px;opacity:0.5;font-size:12px">پیامی نیست</div>';
                }

                apiCall('chat.php', 'delete_all_msgs', {confirm: true, room_id: currentRoomId}, (d) => {
                    if (d.status === 'success') {
                        showToast(d.message);
                        lastMsgId = 0;
                        oldestMsgId = null;
                        fetchMessages();
                    } else {
                        showToast(d.message || 'خطا در حذف پیام‌ها');
                        fetchMessages();
                    }
                });
            });
        }, 500);
    });
}

function filterList(q){ document.querySelectorAll('#sidebarList .item').forEach(el=>el.style.display=el.textContent.toLowerCase().includes(q.toLowerCase())?'flex':'none'); }

function openInviteModal(roomName = null){ 
    openModal('roomSelectModal');
    const l=document.getElementById('inviteRoomList'); 
    l.innerHTML=''; 
    if(roomName) {
        l.innerHTML = `<div style="text-align:center;padding:10px;">در حال دعوت به اتاق: <b>${roomName}</b></div>
        <div class="input-group" style="margin-top:10px;">
            <input type="text" id="inviteUserDirect" class="modal-input" placeholder="نام کاربری..." style="width:100%;margin-bottom:10px;">
            <button onclick="sendDirectInvite('${roomName}')" class="modal-btn btn-green">ارسال دعوت</button>
        </div>`;
        return;
    }

    if (allRoomsCache && allRoomsCache.length > 0) {
        allRoomsCache.forEach(r => {
            if(r.name === 'گفتگوی عمومی' || r.name === 'public') return;
            const d = document.createElement('div'); 
            d.className='item'; 
            d.innerHTML = `<span>${r.type === 'private' ? '🔒' : '💬'} ${r.name}</span>`; 
            d.onclick=()=>{ 
                apiCall('requests.php','invite_user',{username:ctxName, room_name:r.name},()=>showToast('درخواست دعوت ارسال شد')); 
                closeModal('roomSelectModal'); 
            }; 
            l.appendChild(d); 
        });
    } else {
        l.innerHTML = '<div style="text-align:center;padding:10px;color:#94a3b8">هیچ اتاقی یافت نشد</div>';
    }
}

function sendDirectInvite(roomName) {
    const user = document.getElementById('inviteUserDirect').value;
    if(!user) return showToast('نام کاربری را وارد کنید');
    apiCall('requests.php','invite_user',{username:user, room_name:roomName}, (d) => {
        if(d.status === 'success') {
            showToast('درخواست دعوت ارسال شد');
            closeModal('roomSelectModal');
        } else {
            showToast(d.message);
        }
    });
}

function openCreateRoom() {
    openModal('createRoomModal');
    document.getElementById('newRoomName').value = '';
    document.getElementById('newRoomName').focus();
}

function submitCreateRoom(event) {
    if(event) event.preventDefault();
    const name = document.getElementById('newRoomName').value;
    if(!name) return showToast('نام اتاق الزامی است');
    if(name.length < 4) return showToast('نام اتاق باید حداقل ۴ حرف باشد');
    
    const btn = document.getElementById('btnCreateRoomSubmit');
    if(btn) btn.disabled = true;

    apiCall('chat.php', 'create_private_room', {room_name: name}, (d) => {
        if(btn) btn.disabled = false;
        if(d.status === 'success') {
            closeModal('createRoomModal');
            document.getElementById('createdCodeDisplay').textContent = d.invite_code;
            openModal('showCodeModal');
            fetchMessages(); 
        } else {
            showToast(d.message);
        }
    });
}

let foundRoom = null;
let foundCode = null;

function handleSearch(q) {
    q = q.trim();
    if(q.length === 0) {
        foundRoom = null;
        foundCode = null;
        fetchMessages();
        return;
    }
    
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        apiCall('chat.php', 'search_invite_code', {code: q}, (d) => {
            if(d.status === 'success') {
                const isMember = allRoomsCache.some(r => r.id == d.room.id);
                if(isMember) {
                     const inp = document.querySelector('.search-input');
                     foundRoom = null;
                     foundCode = null;
                } else {
                    foundRoom = d.room;
                    foundCode = q;
                }
            } else {
                foundRoom = null;
                foundCode = null;
            }
            fetchMessages();
        });
    }, 500); 
    
    fetchMessages(); 
}

function joinRoomByCode(code) {
    if(!code) return;
    if(Date.now() - lastJoinAttempt < 3000) { showToast('لطفاً صبر کنید...'); return; }
    lastJoinAttempt = Date.now();

    apiCall('chat.php', 'join_via_code', {code: code}, (d) => {
        if(d.status === 'success') {
            showToast('به اتاق ' + d.room_name + ' پیوستید');
            document.querySelector('.search-input').value = '';
            foundRoom = null;
            foundCode = null;
            handleSearch('');
            
            const fd = new FormData();
            fd.append('action', 'send');
            fd.append('csrf_token', csrfToken);
            fd.append('bw_nonce', bwNonce);
            fd.append('room', d.room_name);
            fd.append('room_id', d.room_id);
            fd.append('message', `*** ${currentUsername} به اتاق پیوست ***`);
            
            fetch('chat.php', { method: 'POST', body: fd }).then(() => {
                switchRoom(d.room_name, d.room_name, d.room_id);
            });
        } else {
            const inp = document.querySelector('.search-input');
            inp.classList.add('error');
            setTimeout(() => inp.classList.remove('error'), 500);
            showToast(d.message);
        }
    });
}

function copyInviteCode() {
    const code = document.getElementById('createdCodeDisplay').textContent;
    if(code) {
        const tempInput = document.createElement("input");
        tempInput.style = "position: absolute; left: -1000px; top: -1000px";
        tempInput.value = code;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand("copy");
        document.body.removeChild(tempInput);
        showToast('کپی شد با موفقیت');
    }
}

function copyText(txt) {
    const tempInput = document.createElement("input");
    tempInput.style = "position: absolute; left: -1000px; top: -1000px";
    tempInput.value = txt;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand("copy");
    document.body.removeChild(tempInput);
    showToast('کپی شد');
}

const mb = document.getElementById('micBtn');
const startRec = e => {
    if(e.type==='touchstart') e.preventDefault(); if(isRecording) return;
    startY = e.type.includes('touch')?e.touches[0].clientY:e.clientY;
    navigator.mediaDevices.getUserMedia({audio:true}).then(s=>{
        const options = { mimeType: 'audio/webm' };
        if (!MediaRecorder.isTypeSupported(options.mimeType)) delete options.mimeType;
        
        mediaRecorder = new MediaRecorder(s, options); 
        mediaRecorder.start(); 
        audioChunks = [];
        mediaRecorder.ondataavailable = e => { if (e.data.size > 0) audioChunks.push(e.data); };
        
        isRecording=true; isLocked=false;
        document.getElementById('inputRow').style.display='none'; document.getElementById('voiceUI').style.display='flex';
        let sec=0; recInterval=setInterval(()=>{ sec++; document.getElementById('recTimer').textContent=new Date(sec*1000).toISOString().substr(14,5); },1000);
    }).catch(()=>showToast('دسترسی میکروفون لازم است'));
};
const checkSwipe = e => { if(!isRecording||isLocked)return; let y=e.type.includes('touch')?e.touches[0].clientY:e.clientY; if(startY-y>50){ isLocked=true; document.getElementById('sendVoiceBtn').style.display='block'; } };
const endRec = () => { if(!isRecording||isLocked)return; stopRecManual(); };

function stopRecManual() { 
    if(mediaRecorder && mediaRecorder.state !== 'inactive'){ 
        mediaRecorder.onstop = () => { 
            if(audioChunks.length > 0) {
                const blob = new Blob(audioChunks, { type: 'audio/webm' });
                sendMessage(blob, true); 
            }
            cancelVoice(); 
        }; 
        mediaRecorder.stop(); 
    } 
}

function cancelVoice(){ 
    if(mediaRecorder){ 
        mediaRecorder.onstop=null; 
        if(mediaRecorder.state !== 'inactive') mediaRecorder.stop(); 
        mediaRecorder.stream.getTracks().forEach(t=>t.stop()); 
    } 
    isRecording=false; clearInterval(recInterval); 
    document.getElementById('voiceUI').style.display='none'; 
    document.getElementById('inputRow').style.display='flex'; 
    document.getElementById('sendVoiceBtn').style.display='none'; 
    document.getElementById('recTimer').textContent='00:00'; 
}

mb.addEventListener('mousedown', startRec); document.addEventListener('mouseup', endRec); document.addEventListener('mousemove', checkSwipe);
mb.addEventListener('touchstart', startRec); document.addEventListener('touchend', endRec); document.addEventListener('touchmove', checkSwipe);

if(document.getElementById('chatTitle')) {
    switchRoom('گفتگوی عمومی', 'گفتگوی عمومی', null);
    setInterval(()=>{ if(!document.hidden) fetchMessages(); }, 2000); 
}

function sendBeaconStatus(status) {
    const fd = new FormData();
    fd.append('action', status);
    fd.append('csrf_token', csrfToken);
    navigator.sendBeacon('chat.php', fd);
}

document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'hidden') {
        sendBeaconStatus('set_offline');
    } else {
        fetchMessages();
    }
});

window.addEventListener('beforeunload', function() {
    sendBeaconStatus('set_offline');
});

function openReportModal() {
    openModal('reportUserModal');
    document.getElementById('reportTargetUser').value = '';
    document.getElementById('reportReason').value = '';
    document.getElementById('reportUserSuggestions').innerHTML = '';
    document.getElementById('reportUserSuggestions').classList.remove('show');
    document.getElementById('reportCaptchaInput').value = '';
    refreshReportCaptcha();
}

function refreshReportCaptcha() {
    const btn = document.querySelector('.refresh-btn');
    if (btn) {
        btn.classList.add('rotating');
        setTimeout(() => btn.classList.remove('rotating'), 600);
    }

    const fd = new FormData();
    fd.append('action', 'refresh_report_captcha');
    fd.append('csrf_token', csrfToken);
    fetch('requests.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if(d.status === 'success') {
            document.getElementById('reportCaptchaBox').textContent = `${d.n1} + ${d.n2}`;
        }
    });
}

function submitReportUser() {
    const target = document.getElementById('reportTargetUser').value;
    const reason = document.getElementById('reportReason').value;
    const captcha = document.getElementById('reportCaptchaInput').value;
    const btn = document.getElementById('btnReportSend');

    if (!target || !reason) {
        showToast('لطفاً نام کاربر و دلیل را وارد کنید');
        return;
    }
    if (!captcha) {
        showToast('لطفاً کد امنیتی را وارد کنید');
        return;
    }
    
    const originalText = btn.textContent;
    btn.textContent = 'در حال ارسال...';
    btn.disabled = true;
    btn.style.opacity = '0.7';

    apiCall('requests.php', 'report_user', {target_username: target, reason: reason, captcha: captcha}, (d) => {
        btn.textContent = originalText;
        btn.disabled = false;
        btn.style.opacity = '1';
        
        if(d.status === 'success') {
            showToast(d.message);
            closeModal('reportUserModal');
            document.getElementById('reportTargetUser').value = '';
            document.getElementById('reportReason').value = '';
            document.getElementById('reportCaptchaInput').value = '';
            document.getElementById('reportUserSuggestions').innerHTML = '';
            refreshReportCaptcha();
        } else {
            showToast(d.message);
            refreshReportCaptcha();
            document.getElementById('reportCaptchaInput').value = '';
        }
    });
}

document.getElementById('reportTargetUser').addEventListener('keyup', function() {
    this.value = this.value.replace(/\s{2,}/g, ' ');
    const val = this.value.toLowerCase();
    const sug = document.getElementById('reportUserSuggestions');
    sug.innerHTML = '';
    sug.classList.remove('show');
    
    if (val.length < 1) return;

    if (allUsersCache) {
        let hasMatches = false;
        allUsersCache.forEach(u => {
            if (u.username.toLowerCase().includes(val) && u.username !== currentUsername) {
                hasMatches = true;
                const div = document.createElement('div');
                div.className = 'suggestion-item';
                div.innerHTML = `<span style="font-size:16px">${u.sticker}</span> ${u.username}`;
                div.onclick = () => {
                    document.getElementById('reportTargetUser').value = u.username;
                    sug.innerHTML = '';
                    sug.classList.remove('show');
                };
                sug.appendChild(div);
            }
        });
        if(hasMatches) sug.classList.add('show');
    }
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('#reportTargetUser') && !e.target.closest('#reportUserSuggestions')) {
        document.getElementById('reportUserSuggestions').classList.remove('show');
    }
});

const dragOverlay = document.getElementById('dragOverlay');
let dragCounter = 0;

document.body.addEventListener('dragenter', (e) => {
    e.preventDefault();
    dragCounter++;
    dragOverlay.classList.add('active');
});

document.body.addEventListener('dragleave', (e) => {
    e.preventDefault();
    dragCounter--;
    if (dragCounter === 0) {
        dragOverlay.classList.remove('active');
    }
});

document.body.addEventListener('dragover', (e) => {
    e.preventDefault();
});

document.body.addEventListener('drop', (e) => {
    e.preventDefault();
    dragCounter = 0;
    dragOverlay.classList.remove('active');
    
    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        if(e.dataTransfer.files.length > 5) {
            showToast('حداکثر ۵ فایل می‌توانید انتخاب کنید');
            return;
        }

        const dt = new DataTransfer();
        for(let i=0; i<e.dataTransfer.files.length; i++) {
            if (e.dataTransfer.files[i].size > MAX_FILE_SIZE) {
                showToast(`حجم فایل ${e.dataTransfer.files[i].name} بیشتر از حد مجاز است`);
                continue;
            }
            dt.items.add(e.dataTransfer.files[i]);
        }
        
        if (dt.files.length > 0) {
            document.getElementById('fileInput').files = dt.files;
            document.getElementById('fileInput').onchange();
        }
    }
});

</script>
</body>
</html>