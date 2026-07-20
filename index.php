<?php
session_start();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/units.php';

$loggedIn = isLoggedIn();
$portalHome = $loggedIn ? loginRedirectPath(getUserType()) : '/login.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>عمادة شؤون الطلاب — الإدارة العامة للإرشاد والرعاية الطلابية</title>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="/assets/css/style.css" rel="stylesheet">
<link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
<style>
body {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #0b2e4d 0%, #103754 50%, #0891b2 100%);
    padding: 30px 16px;
}
.landing-card {
    background: #fff;
    border-radius: 22px;
    padding: 44px 40px;
    width: 100%;
    max-width: 900px;
    box-shadow: 0 20px 50px rgba(0,0,0,.25);
    text-align: center;
}
.landing-icon {
    width: 70px; height: 70px; border-radius: 18px;
    background: #e0f2fe; color: #103754;
    display: flex; align-items: center; justify-content: center;
    font-size: 2.1rem; margin: 0 auto 16px;
}
.landing-card h1 { font-size: 1.35rem; font-weight: 800; color: #103754; margin-bottom: 4px; }
.landing-card p.sub { color: #64748b; font-size: .9rem; margin-bottom: 30px; }
.landing-card .badges-grid { justify-content: center; }
.btn-enter { background: linear-gradient(135deg, #0b2e4d, #0891b2); border: none; font-weight: 700; }
</style>
</head>
<body>
<div class="landing-card">
    <div class="landing-icon"><i class="bi bi-mortarboard-fill"></i></div>
    <h1>جامعة بيشة — عمادة شؤون الطلاب</h1>
    <p class="sub">الإدارة العامة للإرشاد والرعاية الطلابية — بوابات الوحدات والمديرين والمشرفين</p>

    <div class="badges-grid mb-4">
        <?php foreach (array_keys(departmentRegistry()) as $key): ?>
            <?= renderDeptBadge($key) ?>
        <?php endforeach; ?>
    </div>

    <a href="<?= htmlspecialchars($portalHome) ?>" class="btn btn-enter btn-primary text-white px-5 py-2">
        <i class="bi bi-box-arrow-in-left me-2"></i><?= $loggedIn ? 'الذهاب إلى لوحتي' : 'تسجيل الدخول' ?>
    </a>
</div>
</body>
</html>
