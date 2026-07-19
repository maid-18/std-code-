<?php
$pageTitle = $pageTitle ?? 'الإدارة العامة للرعاية والإرشاد';
$activePage = $activePage ?? basename($_SERVER['SCRIPT_NAME']);
$navItems = [
    'dashboard.php'       => ['icon' => 'bi-speedometer2',      'label' => 'لوحة التحكم'],
    'requests.php'        => ['icon' => 'bi-inbox-fill',        'label' => 'جميع الطلبات'],
    'reports.php'         => ['icon' => 'bi-file-earmark-bar-graph-fill', 'label' => 'التقارير'],
    'profile.php'         => ['icon' => 'bi-person-circle',     'label' => 'الملف الشخصي'],
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> — الإدارة العامة للرعاية والإرشاد</title>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="/assets/css/style.css" rel="stylesheet">
<?= $extraCss ?? '' ?>
</head>
<body>

<div class="std-shell">
    <aside class="std-sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-shield-heart"></i>
            <div>
                <div class="brand-title">الإدارة العامة</div>
                <div class="brand-sub">للرعاية والإرشاد</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <?php foreach ($navItems as $file => $item): ?>
                <a href="/care_guidance_admin/<?= $file ?>" class="nav-link <?= $activePage === $file ? 'active' : '' ?>">
                    <i class="bi <?= $item['icon'] ?>"></i>
                    <span><?= $item['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <a href="/backend/logout.php" class="nav-link text-danger">
                <i class="bi bi-box-arrow-right"></i>
                <span>تسجيل الخروج</span>
            </a>
        </div>
    </aside>

    <div class="std-main">
        <header class="std-topbar">
            <button class="btn btn-sm btn-light d-lg-none" id="sidebarToggle"><i class="bi bi-list"></i></button>
            <div class="ms-auto d-flex align-items-center gap-3">
                <div class="text-end">
                    <div class="fw-bold small"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></div>
                    <div class="text-muted" style="font-size:.72rem;">الإدارة العامة للرعاية والإرشاد</div>
                </div>
                <img src="<?= getUserAvatar($_SESSION['profile_image'] ?? null) ?>" class="topbar-avatar" alt="">
            </div>
        </header>
        <main class="std-content">
