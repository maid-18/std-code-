<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/units.php';

requireManager();

$fullName = $_SESSION['full_name'] ?? '';

/* إحصائيات كل وحدة (الوحدات الست) */
$unitStats = [];
$total = 0; $newCount = 0; $done = 0;
foreach (unitRegistry() as $key => $cfg) {
    $src = unitSource($key);
    $t = (int)$pdo->query("SELECT COUNT(*) FROM {$src['table']}")->fetchColumn();
    $n = (int)$pdo->query("SELECT COUNT(*) FROM {$src['table']} WHERE {$src['new']}")->fetchColumn();
    $d = (int)$pdo->query("SELECT COUNT(*) FROM {$src['table']} WHERE {$src['done']}")->fetchColumn();
    $unitStats[$key] = ['total' => $t, 'new' => $n, 'done' => $d];
    $total += $t; $newCount += $n; $done += $d;
}

$managerCfg = departmentRegistry()['manager'];

$navBase          = '/manager/';
$brandIcon        = 'bi-person-gear';
$brandTitle       = 'مدير النظام';
$brandSub         = 'عمادة شؤون الطلاب';
$roleLabel        = 'مدير النظام';
$portalAccent     = $managerCfg['accent'];
$portalAccentDark = $managerCfg['accent_dark'];
$navItems = [
    'dashboard.php' => ['icon' => 'bi-speedometer2', 'label' => 'لوحة التحكم'],
    'requests.php'  => ['icon' => 'bi-inbox-fill',   'label' => 'جميع الطلبات', 'href' => '/care_guidance_admin/requests.php'],
    'reports.php'   => ['icon' => 'bi-file-earmark-bar-graph-fill', 'label' => 'التقارير', 'href' => '/care_guidance_admin/reports.php'],
    'profile.php'   => ['icon' => 'bi-person-circle', 'label' => 'الملف الشخصي', 'href' => '/care_guidance_admin/profile.php'],
];

$pageTitle = 'لوحة مدير النظام';
$activePage = 'dashboard.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="welcome-banner" style="background:linear-gradient(135deg, <?= $managerCfg['accent_dark'] ?>, #1a5f7a);">
    <div class="row align-items-center">
        <div class="col">
            <h2>مرحباً، <?= htmlspecialchars(explode(' ', $fullName)[0] ?? '') ?></h2>
            <p>الإشراف الكامل على جميع جهات نظام الإرشاد والرعاية الطلابية</p>
        </div>
        <div class="col-auto d-none d-md-block">
            <?= renderDeptBadge('manager', '') ?>
        </div>
    </div>
    <i class="bi bi-person-gear banner-bg-icon"></i>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary-soft text-primary"><i class="bi bi-inbox-fill"></i></div>
                <div><div class="stat-number text-primary"><?= $total ?></div><div class="stat-label">إجمالي طلبات الوحدات</div></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="border-inline-start-color:#e67e22;">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#fef9e7;color:#e67e22;"><i class="bi bi-hourglass-split"></i></div>
                <div><div class="stat-number" style="color:#e67e22;"><?= $newCount ?></div><div class="stat-label">طلبات جديدة</div></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="border-inline-start-color:#27ae60;">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#eafaf1;color:#27ae60;"><i class="bi bi-check-circle-fill"></i></div>
                <div><div class="stat-number" style="color:#27ae60;"><?= $done ?></div><div class="stat-label">مكتملة</div></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card" style="border-inline-start-color:#8e44ad;">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#f5eef9;color:#8e44ad;"><i class="bi bi-speedometer2"></i></div>
                <div><div class="stat-number" style="color:#8e44ad;"><?= $total ? round($done / $total * 100) : 0 ?>%</div><div class="stat-label">معدل الإنجاز</div></div>
            </div>
        </div>
    </div>
</div>

<div class="std-card p-4 mb-4">
    <h5 class="fw-bold mb-3"><i class="bi bi-grid-3x3-gap-fill me-2 text-primary"></i>جهات النظام</h5>
    <p class="text-muted small mb-4">اضغط على أي جهة للانتقال إلى لوحتها أو تقريرها</p>
    <div class="badges-grid">
        <?= renderDeptBadge('manager', '') ?>
        <?= renderDeptBadge('care_guidance', '/care_guidance_admin/dashboard.php') ?>
        <?php foreach (array_keys(unitRegistry()) as $key): ?>
            <?= renderDeptBadge($key, '/care_guidance_admin/reports.php?unit=' . urlencode($key)) ?>
        <?php endforeach; ?>
    </div>
</div>

<div class="std-card p-4">
    <h5 class="fw-bold mb-3"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>ملخص الوحدات</h5>
    <div class="table-responsive">
        <table class="table table-std table-hover mb-0">
            <thead><tr><th>الوحدة</th><th>إجمالي الطلبات</th><th>جديدة</th><th>مكتملة</th><th>معدل الإنجاز</th><th></th></tr></thead>
            <tbody>
            <?php foreach (unitRegistry() as $key => $cfg): $s = $unitStats[$key]; ?>
                <tr>
                    <td class="fw-600 small"><i class="bi <?= $cfg['icon'] ?> me-2" style="color:<?= $cfg['accent'] ?>;"></i><?= htmlspecialchars($cfg['label']) ?></td>
                    <td class="small"><?= $s['total'] ?></td>
                    <td class="small text-warning fw-bold"><?= $s['new'] ?></td>
                    <td class="small text-success fw-bold"><?= $s['done'] ?></td>
                    <td class="small"><?= $s['total'] ? round($s['done'] / $s['total'] * 100) : 0 ?>%</td>
                    <td><a href="/care_guidance_admin/reports.php?unit=<?= urlencode($key) ?>" class="btn btn-sm btn-outline-primary px-2"><i class="bi bi-file-earmark-bar-graph"></i> التقرير</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
