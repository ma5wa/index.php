<?php
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدخال المستخدم</title>
</head>
<body>
    <h2>إدخال بيانات المستخدم</h2>

    <form method="POST" action="">
        <label>اسم المستخدم:</label><br>
        <input type="text" name="username" required><br><br>

        <label>رقم الهاتف:</label><br>
        <input type="number" name="phone" required><br><br>

        <button type="submit">إرسال</button>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_POST['username'];
        $phone = $_POST['phone'];

        echo "اسم المستخدم: " . $username . "<br>";
        echo "رقمه: " . $phone;
    }
    ?>
</body>
</html>