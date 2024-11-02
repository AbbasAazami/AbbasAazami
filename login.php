<?php
// نمایش خطاها
ini_set('display_errors', 1);
error_reporting(E_ALL);

// اتصال به دیتابیس
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "panel";

$conn = new mysqli($servername, $username, $password, $dbname);

// بررسی اتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// بررسی درخواست POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'];
    $password = $input['password'];

    // استفاده از Prepared Statement برای جستجوی کاربر
    $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // بررسی رمز عبور
        if (password_verify($password, $row['password'])) {
            session_start();
            $_SESSION['username'] = $username; // ذخیره نام کاربری در جلسه
            echo json_encode(["success" => true, "message" => "ورود با موفقیت انجام شد."]);
        } else {
            echo json_encode(["success" => false, "message" => "رمز عبور نادرست است."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "کاربری با این نام وجود ندارد."]);
    }

    $stmt->close();
}

$conn->close();
?>
