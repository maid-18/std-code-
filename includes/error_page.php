<?php
/**
 * صفحة خطأ موحّدة الشكل (403/404/500...) بمعزل عن header.php حتى تعمل
 * حتى قبل توفر بيانات الجلسة الكاملة.
 */
function renderErrorPage(int $httpCode, string $title, string $message, string $backUrl = '/login.php', string $backLabel = 'العودة لتسجيل الدخول'): never
{
    http_response_code($httpCode);
    ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title) ?> — الإدارة العامة للرعاية والإرشاد</title>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
<style>
body {
    font-family: 'Tajawal', sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #0b2e4d 0%, #103754 50%, #0891b2 100%);
}
.error-card { background: #fff; border-radius: 18px; padding: 44px; width: 100%; max-width: 420px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,.25); }
.error-code { font-size: 3.2rem; font-weight: 800; color: #103754; line-height: 1; }
.error-icon { width: 64px; height: 64px; border-radius: 50%; background: #fef2f2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 16px; }
</style>
</head>
<body>
<div class="error-card">
    <div class="error-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
    <div class="error-code"><?= (int)$httpCode ?></div>
    <h5 class="fw-bold mt-2 mb-2"><?= htmlspecialchars($title) ?></h5>
    <p class="text-muted mb-4"><?= htmlspecialchars($message) ?></p>
    <a href="<?= htmlspecialchars($backUrl) ?>" class="btn btn-primary px-4"><?= htmlspecialchars($backLabel) ?></a>
</div>
</body>
</html>
    <?php
    exit;
}
