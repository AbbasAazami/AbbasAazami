<?php
// dashboard.php

// نمایش خطاها
ini_set('display_errors', 1);
error_reporting(E_ALL);

// بررسی اینکه کاربر وارد شده است یا خیر
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html"); // به صفحه ورود هدایت می‌شود
    exit();
}

// اتصال به دیتابیس
$servername = "localhost"; // نام سرور
$username = "root"; // نام کاربری دیتابیس
$password = ""; // رمز عبور دیتابیس
$dbname = "panel"; // نام دیتابیس

$conn = new mysqli($servername, $username, $password, $dbname);

// بررسی اتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// دریافت اطلاعات کاربر
$current_user = $_SESSION['username'];
$stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
$stmt->bind_param("s", $current_user);
$stmt->execute();
$result = $stmt->get_result();

// بررسی اینکه آیا کاربر وجود دارد
if ($result->num_rows === 0) {
    die("کاربر یافت نشد.");
}

$user_data = $result->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد کاربری</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Vazir&display=swap" rel="stylesheet"> <!-- اضافه کردن فونت -->
    <style>
        body {
            background-color: #e9ecef; /* رنگ پس‌زمینه ملایم */
            font-family: 'Vazir', sans-serif; /* استفاده از فونت Vazir */
            margin: 0; /* حذف حاشیه */
            padding: 0; /* حذف padding */
            overflow-x: hidden; /* جلوگیری از اسکرول افقی */
        }
        .loading-screen {
            position: fixed; /* موقعیت ثابت */
            top: 0; /* از بالا */
            left: 0; /* از چپ */
            width: 100%; /* عرض کامل */
            height: 100%; /* ارتفاع کامل */
            background-color: rgba(255, 255, 255, 0.8); /* پس‌زمینه نیمه شفاف */
            display: flex; /* استفاده از فلیکس باکس */
            justify-content: center; /* مرکز قرار دادن افقی */
            align-items: center; /* مرکز قرار دادن عمودی */
            z-index: 1000; /* قرار دادن در بالای سایر عناصر */
        }
        .container {
            margin-top: 20px; /* فاصله بیشتر از بالا */
            position: relative; /* برای استفاده از موقعیت مطلق در داخل */
            display: flex; /* استفاده از فلیکس باکس */
            justify-content: center; /* مرکز قرار دادن افقی */
            align-items: center; /* مرکز قرار دادن عمودی */
            height: calc(100vh - 60px); /* ارتفاع به اندازه کل صفحه منهای ارتفاع فوتر */
        }
        .background-image {
            background-image: url('https://cdn.nody.ir/files/2021/08/12/nody-%D8%B9%DA%A9%D8%B3-%D9%87%D8%A7%DB%8C-gta-san-1628750423.jpg'); /* مسیر عکس پس‌زمینه */
            background-size: cover; /* پوشش کامل */
            background-position: center; /* مرکز قرار دادن */
            background-repeat: no-repeat; /* جلوگیری از تکرار تصویر */
            width: 100%; /* عرض کامل */
            height: 100%; /* ارتفاع به اندازه کل صفحه */
            border-radius: 20px; /* گرد کردن گوشه‌ها */
            position: relative; /* برای قرار دادن متن روی تصویر */
            overflow: hidden; /* جلوگیری از خروج محتوا از محدوده */
        }
        .settings-button {
            position: absolute;
            top: 20px; /* موقعیت عمودی */
            right: 20px; /* موقعیت افقی */
            cursor: pointer; /* نشانگر ماوس */
            background: transparent; /* پس‌زمینه شفاف */
            border: none; /* بدون حاشیه */
            color: white; /* رنگ آیکون */
            font-size: 2em; /* اندازه آیکون */
        }
        .settings-menu {
            display: none; /* مخفی بودن منوی تنظیمات */
            position: fixed; /* ثابت در صفحه */
            top: 0; /* از بالا */
            right: 0; /* از راست */
            background: linear-gradient(135deg, #343a40, #495057); /* ترکیب رنگ‌های تیره */
            width: 250px; /* افزایش عرض منوی تنظیمات */
            height: 100vh; /* ارتفاع به اندازه کل صفحه */
            padding: 20px; /* padding برای منوی تنظیمات */
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.2); /* سایه ملایم */
            z-index: 10; /* قرار دادن در بالای سایر عناصر */
            transition: transform 0.3s ease; /* انیمیشن برای باز و بسته شدن */
            transform: translateX(100%); /* پنهان کردن منو */
        }
        .settings-menu.active {
            display: block; /* نمایش منو */
            transform: translateX(0); /* نمایش منو */
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #6c757d;
            position: absolute; /* موقعیت مطلق */
            bottom: 10px; /* فاصله از پایین */
            left: 0; /* از چپ */
            right: 0; /* از راست */
        }
        /* اضافه کردن انیمیشن */
        .fade-in {
            animation: fadeIn 1s ease-in-out;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        .close-button {
            margin-top: auto; /* قرار دادن در انتهای منو */
            width: 100%; /* عرض کامل */
            text-align: center; /* مرکز قرار دادن متن */
        }
        .personal-settings {
            color: white; /* رنگ متن */
            cursor: pointer; /* نشانگر ماوس */
            margin: 10px 0; /* فاصله عمودی */
        }
        .personal-settings-menu {
            display: none; /* مخفی بودن منوی تنظیمات شخصی */
            margin-top: 10px; /* فاصله از بالا */
            padding: 10px; /* padding برای منوی تنظیمات شخصی */
            background: rgba(255, 255, 255, 0.1); /* پس‌زمینه نیمه شفاف */
            border-radius: 5px; /* گرد کردن گوشه‌ها */
            width: 250px; /* افزایش عرض منوی تنظیمات شخصی */
        }
    </style>
    <script>
        function confirmLogout() {
            return confirm("آیا مطمئن هستید که می‌خواهید خارج شوید؟");
        }

        function toggleSettingsMenu() {
            const menu = document.querySelector('.settings-menu');
            menu.classList.toggle('active'); // اضافه کردن یا حذف کلاس active
        }

        function togglePersonalSettings() {
            const personalMenu = document.querySelector('.personal-settings-menu');
            personalMenu.classList.toggle('active'); // اضافه کردن یا حذف کلاس active
            personalMenu.style.display = personalMenu.style.display === 'block' ? 'none' : 'block'; // تغییر نمایش
        }

        // تابع برای پنهان کردن صفحه بارگذاری
        window.onload = function() {
            const loadingScreen = document.querySelector('.loading-screen');
            loadingScreen.style.display = 'none'; // پنهان کردن صفحه بارگذاری
        }
    </script>
</head>
<body>
<div class="loading-screen">
    <h1>در حال بارگذاری...</h1> <!-- متن بارگذاری -->
</div>
<div class="container mt-5 fade-in">
    <div class="background-image">
        <button class="settings-button" onclick="toggleSettingsMenu();">☰</button> <!-- دکمه تنظیمات -->
        <div class="settings-menu">
            <h4 style="color: white;">تنظیمات</h4>
            <div class="personal-settings" onclick="togglePersonalSettings();">تنظیمات شخصی</div> <!-- گزینه تنظیمات شخصی -->
            <div class="personal-settings-menu">
                <img src="https://eramblog.com/img/th/1713345874_4075225.jpg" alt="Profile Image" class="profile-image" style="width: 100%; border-radius: 50%; margin-bottom: 10px;"> <!-- عکس پروفایل -->
                <div style="color: white;"><strong>نام کاربری:</strong> <?php echo htmlspecialchars($user_data['username']); ?></div>
                <div style="color: white;"><strong>شماره تلفن:</strong> <?php echo htmlspecialchars($user_data['phone']); ?></div>
            </div> <!-- منوی تنظیمات شخصی -->
            <hr>
            <div class="close-button">
                <button class="btn btn-danger" onclick="toggleSettingsMenu();">بستن</button> <!-- دکمه بستن -->
            </div>
        </div> <!-- منوی تنظیمات -->
    </div> <!-- بخش پس‌زمینه -->
</div>
<div class="footer">
    <p>© 2024 تمامی حقوق محفوظ است.</p>
</div>
</body>
</html>
