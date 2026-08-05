# 🚂 راهنمای استقرار بر روی Railway

## مراحل استقرار

### 1. آماده‌سازی اولیه

1. **ایجاد حساب کاربری Railway**
   - به [railway.app](https://railway.app) رفته و ثبت نام کنید

2. **فورک یا Clone کردن Repository**
   ```bash
   git clone https://github.com/aivid9/BlackWacker-Secure-Chat.git
   cd BlackWacker-Secure-Chat
   ```

### 2. تنظیم Database بر روی Railway

1. در داشبورد Railway، روی **+ New Project** کلیک کنید
2. **MySQL** را انتخاب کنید
3. نام پروژه را وارد کنید (مثل: `blackwacker-chat`)
4. بعد از ایجاد، **Credentials** را کپی کنید

### 3. استقرار Application

1. از **GitHub** متصل شوید
2. Repository را انتخاب کنید
3. **Railway** خودکار Procfile را تشخیص می‌دهد

### 4. تنظیم Environment Variables

در داشبورد Railway، تب **Variables** را باز کنید و این متغیرها را اضافه کنید:

```env
# Database (از MySQL service Railway کپی کنید)
MYSQLHOST=<your_railway_host>
MYSQLPORT=3306
MYSQLDATABASE=railway
MYSQLUSER=root
MYSQLPASSWORD=<your_password>

# Admin Account
ADMIN_USERNAME=admin
ADMIN_DISPLAY_NAME=مدیر
ADMIN_PASSWORD=<choose_a_strong_password>

# Security - VERY IMPORTANT!
ENCRYPTION_KEY=<generate_a_random_secure_key>

# Optional Settings
MESSAGE_LIFETIME=259200
FILE_SIZE_LIMIT=5242880
SPAM_LIMIT_COUNT=10
SPAM_LIMIT_TIME=10
BAN_DURATION=120
ENABLE_INTRO_POPUP=true
```

### 5. اتصال Database

بعد از اضافه کردن MySQL:

1. روی **MySQL Service** کلیک کنید
2. **Variables** تب را باز کنید
3. مقادیر `MYSQLHOST`, `MYSQLUSER`, `MYSQLPASSWORD` را کپی کنید
4. این مقادیر را به Application's Variables اضافه کنید

### 6. تغییر فایل Config در Application

به جای استفاده از `config.php`، اپلیکیشن از `config-railway.php` استفاده خواهد کرد.

برای فعال‌سازی:
1. فایل `config-railway.php` را به `config.php` تغییر نام دهید یا
2. تمام فایل‌های PHP را به‌روز کنید تا `config-railway.php` را import کنند

### 7. Public Domain

1. در داشبورد Railway، **App Settings** را باز کنید
2. **Generate Domain** کلیک کنید
3. Domain شما ایجاد شد!

## مشکلات رایج و حل‌ها

### 1. خطای اتصال Database

**مشکل**: `Connection refused` یا `Unknown MySQL host`

**حل**:
- اطمینان دهید MySQL service بالا است
- MYSQLHOST, MYSQLUSER, MYSQLPASSWORD را بررسی کنید
- در لاگ‌های Railway مشکل را پیدا کنید

### 2. فایل‌های Upload نمی‌شود

**مشکل**: خطای در uploads directory

**حل**:
- Railway به طور خودکار `uploads` directory را ایجاد می‌کند
- اطمینان دهید permissions صحیح است

### 3. صفحه Banned یا IP Issue

**مشکل**: IP شما مسدود می‌شود

**حل**:
- Railway Proxy استفاده می‌کند، بنابراین IP forwarding تنظیم شده است
- `get_client_ip()` در `config-railway.php` این را handle می‌کند

### 4. Encryption Key

**مهم**: ENCRYPTION_KEY را حتماً تغییر دهید!

```bash
# برای ایجاد کلید امن:
openssl rand -base64 32
```

## نکات مهم

⚠️ **امنیت**:
- ENCRYPTION_KEY را قبل از استقرار تغییر دهید
- Admin password را قوی انتخاب کنید
- HTTPS خودکار فعال است در Railway

📁 **Uploads**:
- فایل‌های Upload شده در Ephemeral Storage ذخیره می‌شوند
- اگر deployment restart شود، فایل‌ها حذف می‌شوند
- برای persistence، می‌توانید Volume اضافه کنید

🔄 **Restart**:
- اپلیکیشن بدون downtime نمی‌شود
- Database schema خودکار migrate می‌شود

## بهینه‌سازی برای Railway

✅ PHP Version: 7.4+
✅ Extensions: PDO, OpenSSL (standard)
✅ Memory Limit: تنظیم شده (512MB)
✅ Session Handling: Secure

## مراجع

- [Railway Docs](https://docs.railway.app)
- [PHP on Railway](https://docs.railway.app/guides/php)
- [MySQL on Railway](https://docs.railway.app/guides/mysql)

---

سوالات یا مشکلات؟ Issues را بررسی کنید یا گزارش دهید.
