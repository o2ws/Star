<?php
session_start();
require_once '../config.php';

// إذا مسجل دخول بالفعل
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error = '';

if (isset($_POST['login'])) {

    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);

    // قراءة بيانات الأدمن
    $admin_data = get_json_data(ADMIN_FILE);

    if (
        isset($admin_data['username']) &&
        isset($admin_data['password']) &&
        $admin_data['username'] === $user &&
        password_verify($pass, $admin_data['password'])
    ) {

        $_SESSION['admin_logged_in'] = true;

        header("Location: index.php");
        exit;

    } else {
        $error = "اسم المستخدم أو كلمة المرور غير صحيحة";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تسجيل دخول الإدارة</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

<style>
body{
    background:#0b0b18;
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    font-family:Tahoma;
}

.login-box{
    width:350px;
    background:#15152b;
    border:1px solid #2d2d55;
    border-radius:15px;
    padding:30px;
    color:white;
    box-shadow:0 0 20px rgba(0,0,0,.4);
}

.title{
    text-align:center;
    margin-bottom:25px;
    font-size:28px;
    color:#7c3aed;
    font-weight:bold;
}

.form-control{
    background:#1f1f3d !important;
    border:1px solid #333366 !important;
    color:white !important;
    margin-bottom:15px;
    height:45px;
}

.form-control:focus{
    box-shadow:none !important;
    border-color:#7c3aed !important;
}

.btn-login{
    background:#7c3aed;
    border:none;
    width:100%;
    height:45px;
    color:white;
    font-weight:bold;
}

.btn-login:hover{
    background:#6d28d9;
}

.error{
    background:#ff000020;
    border:1px solid red;
    color:#ff8080;
    padding:10px;
    border-radius:8px;
    margin-bottom:15px;
    text-align:center;
}
</style>
</head>

<body>

<div class="login-box">

    <div class="title">Nova TV</div>

    <?php if($error): ?>
        <div class="error">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <input
            type="text"
            name="username"
            class="form-control"
            placeholder="اسم المستخدم"
            required
        >

        <input
            type="password"
            name="password"
            class="form-control"
            placeholder="كلمة المرور"
            required
        >

        <button type="submit" name="login" class="btn btn-login">
            تسجيل الدخول
        </button>

    </form>

</div>

</body>
</html>