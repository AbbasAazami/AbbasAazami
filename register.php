<?php
// نمایش خطاها
ini_set('display_errors', 1);
error_reporting(E_ALL);

// اتصال به دیتابیس
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "panel";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    // بررسی اتصال
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // تابع اعتبارسنجی شماره تلفن
    function isValidPhoneNumber($phone) {
        return preg_match('/^09\d{9}$/', $phone);
    }

    // بررسی درخواست POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // پاکسازی ورودی‌ها
        $username = trim($input['username']);
        $password = trim($input['password']);
        $phone = trim($input['phone']);

        // اعتبارسنجی شماره تلفن
        if (!isValidPhoneNumber($phone)) {
            echo json_encode(["success" => false, "message" => "شماره تلفن نامعتبر است."]);
            exit();
        }

        // بررسی وجود شماره تلفن و نام کاربری در دیتابیس
        $stmt = $conn->prepare("SELECT * FROM users WHERE phone=? OR username=?");
        $stmt->bind_param("ss", $phone, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $existingUser = $result->fetch_assoc();
            if ($existingUser['phone'] === $phone) {
                echo json_encode(["success" => false, "message" => "این شماره تلفن قبلاً ثبت‌نام شده است."]);
            } else {
                echo json_encode(["success" => false, "message" => "این نام کاربری قبلاً ثبت‌نام شده است."]);
            }
            exit();
        }

        // ذخیره کاربر در دیتابیس
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, phone) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed_password, $phone);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "ثبت‌نام با موفقیت انجام شد.", "redirect" => "dashboard.php"]);
        } else {
            echo json_encode(["success" => false, "message" => "خطا در ثبت‌نام."]);
        }

        $stmt->close();
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
} finally {
    $conn->close();
}
?>
