<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . loginRedirectPath(getUserType()));
    exit;
}

$csrfToken = generateCsrfToken();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'انتهت صلاحية الجلسة، يرجى إعادة المحاولة.';
    } else {
        $email    = clean($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $error = 'يرجى إدخال البريد الإلكتروني وكلمة المرور.';
        } elseif (loginUser($pdo, $email, $password)) {
            header('Location: ' . loginRedirectPath(getUserType()));
            exit;
        } else {
            $error = 'البريد الإلكتروني أو كلمة المرور غير صحيحة.';
        }
    }
    $csrfToken = generateCsrfToken();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تسجيل الدخول — الإدارة العامة للرعاية والإرشاد</title>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body {
    font-family: 'Tajawal', sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #0b2e4d 0%, #103754 50%, #0891b2 100%);
}
.login-card { background: #fff; border-radius: 18px; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 20px 50px rgba(0,0,0,.25); }
.login-icon { width: 60px; height: 60px; border-radius: 16px; background: #e0f2fe; color: #103754; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 14px; }
.login-card h1 { font-size: 1.15rem; font-weight: 800; text-align: center; color: #103754; margin-bottom: 2px; }
.login-card p.sub { text-align: center; color: #64748b; font-size: .82rem; margin-bottom: 24px; }
.btn-login { background: linear-gradient(135deg, #0b2e4d, #0891b2); border: none; font-weight: 700; }
</style>
</head>
<body>
<div class="login-card">
    <div class="login-icon"><i class="bi bi-shield-heart"></i></div>
    <h1>الإدارة العامة للرعاية والإرشاد</h1>
    <p class="sub">تسجيل دخول المشرفين والإداريين</p>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <div class="mb-3">
            <label class="form-label small fw-bold">البريد الإلكتروني</label>
            <input type="email" name="email" class="form-control" required autofocus value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="mb-4">
            <label class="form-label small fw-bold">كلمة المرور</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-login btn-primary w-100 text-white py-2">تسجيل الدخول</button>
    </form>
</div>
</body>
</html>
