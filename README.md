# 💬 BlackWacker Secure Chat | چت روم امن بلک واکر

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Vanilla JS](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![Security](https://img.shields.io/badge/Security-AES--256-success?style=for-the-badge)
![Creator](https://img.shields.io/badge/Creator-Pouya_Fakham-818cf8?style=for-the-badge)

**Created by Pouya Fakham | [blackwacker.com](https://blackwacker.com)**

---

## 🌟 About The Project | درباره پروژه

**🇬🇧 English:**
**BlackWacker** is a highly secure, modern, and real-time chat application built entirely with vanilla PHP and MySQL. It focuses on privacy, seamless user experience, and robust administration without relying on heavy frameworks. Designed by **Pouya Fakham**, this system features end-to-end simulated encryption (AES-256), voice messaging, private rooms, and a unique passwordless authentication system.

**🇮🇷 فارسی:**
**بلک واکر** یک سیستم چت روم تحت وب پیشرفته، امن و بلادرنگ است که با PHP خام و MySQL توسعه یافته است. این پروژه که توسط **پویا فخام** ([blackwacker.com](https://blackwacker.com)) طراحی شده، با تمرکز بر امنیت داده ها (رمزنگاری فایل ها و پیام ها با AES-256)، رابط کاربری مدرن، و مدیریت یکپارچه طراحی شده است و نیازی به فریم ورک های سنگین ندارد.

---

## ✨ Key Features | ویژگی های کلیدی

### 🛡️ Advanced Security | امنیت فوق العاده
* **🇬🇧 EN:** E2E simulated encryption for all messages and uploaded files using the `AES-256-CBC` algorithm before saving to the database. Includes Anti-Spam (auto-mute) and CSRF protection.
* **🇮🇷 FA:** رمزنگاری تمام پیام ها و فایل ها با الگوریتم قدرتمند `AES-256-CBC` پیش از ذخیره در دیتابیس. دارای سیستم آنتی اسپم (میوت خودکار) و محافظت در برابر حملات CSRF.

### 🔑 Passwordless Auth | ورود بدون رمز عبور
* **🇬🇧 EN:** Smart authentication based on Device Tokens and IP. Uses an 8-digit one-time "Security Code" for account recovery instead of traditional passwords.
* **🇮🇷 FA:** احراز هویت هوشمند مبتنی بر توکن دستگاه و IP. استفاده از «کد امنیتی ۸ رقمی» یکبار مصرف برای بازگردانی اکانت به جای پسوردهای سنتی و ناامن.

### 🎤 Rich Media Support | پشتیبانی کامل از رسانه
* **🇬🇧 EN:** Record and send direct voice messages (`.webm`) in the browser. Supports image/video sharing with HTTP chunked streaming (`HTTP_RANGE`) for encrypted media.
* **🇮🇷 FA:** قابلیت ضبط و ارسال مستقیم ویس در مرورگر. ارسال تصاویر و ویدیوها با قابلیت استریم امن (پشتیبانی از `HTTP_RANGE` برای Seek کردن فایل های رمزنگاری شده).

### 🚪 Room Management | مدیریت اتاق ها (ACL)
* **🇬🇧 EN:** Features a main Public Room and allows users to create infinite Private Rooms joined via unique Invite Codes.
* **🇮🇷 FA:** دارای یک گفتگوی عمومی و امکان ساخت بی نهایت اتاق خصوصی توسط کاربران. عضویت در اتاق های خصوصی از طریق "کد دعوت" انجام می شود.

### ⚙️ Pro Admin Dashboard | پنل مدیریت پیشرفته
* **🇬🇧 EN:** Advanced admin panel for permanent/temporary IP banning, global site/upload locks, global notifications, and an automated factory reset timer for the database.
* **🇮🇷 FA:** داشبورد حرفه ای برای مسدودسازی (Ban) کاربران با IP، قفل کردن اضطراری کل سایت یا آپلودها، ارسال اطلاعیه سراسری و تایمر بازنشانی خودکار دیتابیس (Factory Reset).

### 📱 Modern UI/UX | رابط کاربری مدرن
* **🇬🇧 EN:** Fully responsive, mobile-friendly Glassmorphism design with real-time updates (optimized short-polling), context menus, and inline replies.
* **🇮🇷 FA:** طراحی کاملاً واکنش گرا (Responsive) با استایل شیشه ای (Glassmorphism)، دریافت بلادرنگ پیام ها، منوهای کلیک راست و قابلیت ریپلای پیام ها.

---

## 🛠️ Tech Stack | تکنولوژی های استفاده شده

* **Backend:** Vanilla PHP 8.0+ (No framework needed)
* **Database:** MySQL (PDO Interface)
* **Frontend:** HTML5, CSS3, Vanilla JavaScript (DOM manipulation & AJAX)
* **Date & Time:** Jalali (Shamsi) Calendar integration via `jdf.php`

---

## 🚀 Installation | راهنمای نصب و راه اندازی

**🇬🇧 English:**
1. Download or clone the repository and place the files in your server's root directory (or XAMPP/localhost).
2. Open `config.php` and update the database connection variables (`$db_host`, `$db_name`, `$db_user`, `$db_pass`). 
   *(Note: The system features auto-migration and will create all necessary tables automatically on the first run!)*
3. In `config.php`, customize your admin credentials (`$admin_username` & `$admin_password`) and the `ENCRYPTION_KEY`.
4. Open the site in your browser and log in with the admin credentials.

**🇮🇷 فارسی:**
1. کل فایل های ریپازیتوری را دانلود کرده و در روت هاست (یا سرویس لوکال خود مثل XAMPP) قرار دهید.
2. فایل `config.php` را باز کنید و اطلاعات اتصال به دیتابیس (`$db_host`, `$db_name`, `$db_user`, `$db_pass`) را وارد کنید. 
   *(نکته: سیستم در اولین اجرا، تمامی جداول مورد نیاز را به صورت خودکار در دیتابیس می سازد!)*
3. در همان فایل `config.php`، نام کاربری و پسورد مدیریت (`$admin_username` و `$admin_password`) و همچنین کلید رمزنگاری (`ENCRYPTION_KEY`) را تغییر دهید.
4. سایت را در مرورگر باز کنید و با اطلاعات مدیریت وارد شوید.

---

## 📁 File Structure | ساختار فایل ها

| File Name | Description / توضیحات |
| :--- | :--- |
| `config.php` | Core settings, DB connection, auto-migration, and AES encryption functions. |
| `index.php` | Main User Interface (UI) and client-side JavaScript logic. |
| `chat.php` | Core Chat API (handling messages, rooms, typing status). |
| `requests.php` | User management API (login, security codes, reporting, bans). |
| `admin.php` | Professional Admin Dashboard. |
| `file.php` / `voice.php` | Secure media dispatchers (decrypting and streaming uploaded files on the fly). |

---

## 🛡️ Security Warning | هشدار امنیتی
**EN:** Ensure you change the default `ENCRYPTION_KEY` in `config.php` to a strong, unique 32-character string before deploying to production.
**FA:** حتماً کلید رمزنگاری پیش فرض (`ENCRYPTION_KEY`) در فایل `config.php` را قبل از راه اندازی روی سرور اصلی به یک رشته قوی تغییر دهید.

---

## 👨‍💻 Author & Credits | سازنده و توسعه دهنده

* **Developer:** Pouya Fakham (پویا فخام)
* **Website:** [blackwacker.com](https://blackwacker.com)
* **Telegram:** [@PooyaFakham](https://t.me/PooyaFakham)

*(If you use or modify this project, please keep the original credits intact. / در صورت استفاده یا تغییر این پروژه، لطفاً کپی رایت سازنده را حفظ کنید.)*
