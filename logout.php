<?php
// logout.php

session_start();
session_destroy(); // پایان جلسه کاربری
header("Location: index.html"); // به صفحه ورود هدایت می‌شود
exit();
?>
